import { acceptHMRUpdate, defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { createNote, type Note, type NoteStatus } from '@/domain/note';
import { errorMessage } from '@/services/errors';
import { uploadNote } from '@/services/notes-api';
import { deleteNote, getAudio, listNotes, saveAudio, saveNote } from '@/services/note-repository';
import { MIN_RECORDING_MS, pcmToWav } from '@/services/pcm';
import { recognizeSpeech } from '@/services/speechkit';

export const useNotesStore = defineStore('notes', () => {
  const notes = ref<Note[]>([]);
  const ready = ref(false);
  const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
  const processingCount = ref(0);
  const inflight = new Set<string>();
  let queue: Promise<void> = Promise.resolve();
  let networkBound = false;

  const pendingNotes = computed(() => notes.value.filter((note) => note.status !== 'synced'));
  const pendingCount = computed(() => pendingNotes.value.length);
  const processing = computed(() => processingCount.value > 0);

  async function hydrate(): Promise<void> {
    notes.value = await listNotes();
    ready.value = true;
    if (online.value) void syncPending();
  }

  function watchNetwork(): void {
    if (networkBound || typeof window === 'undefined') return;
    networkBound = true;
    online.value = navigator.onLine;

    window.addEventListener('online', () => {
      online.value = true;
      void syncPending();
    });
    window.addEventListener('offline', () => {
      online.value = false;
    });
  }

  async function addRecording(pcm: ArrayBuffer, durationMs: number): Promise<Note> {
    if (durationMs < MIN_RECORDING_MS) throw new Error('Слишком короткая запись.');

    const note = createNote(durationMs);
    await saveNote(note);
    await saveAudio(note.id, pcm);
    notes.value = [note, ...notes.value];
    await processNote(note.id);
    return notes.value.find((item) => item.id === note.id) ?? note;
  }

  function syncPending(): Promise<void> {
    queue = queue.then(async () => {
      const ids = [...notes.value]
        .filter((note) => note.status !== 'synced')
        .sort((left, right) => left.recordedAt.localeCompare(right.recordedAt))
        .map((note) => note.id);

      for (const id of ids) await processNote(id);
    });

    return queue;
  }

  async function retry(id: string): Promise<void> {
    await processNote(id);
  }

  async function remove(id: string): Promise<void> {
    await deleteNote(id);
    notes.value = notes.value.filter((note) => note.id !== id);
  }

  async function playback(id: string): Promise<Blob | null> {
    const pcm = await getAudio(id);
    return pcm ? pcmToWav(pcm) : null;
  }

  async function processNote(id: string): Promise<void> {
    const current = notes.value.find((note) => note.id === id);
    if (!current || current.status === 'synced' || inflight.has(id)) return;
    if (!navigator.onLine) {
      online.value = false;
      await persist(id, { error: 'Нет сети. Заметка останется на устройстве.' });
      return;
    }

    inflight.add(id);
    processingCount.value += 1;

    try {
      let body = current.body;

      if (!body) {
        const transcribing = await persist(id, { status: 'transcribing', error: null });
        if (!transcribing) return;

        try {
          const pcm = await getAudio(id);
          if (!pcm) throw new Error('Локальная запись не найдена.');
          body = await recognizeSpeech(pcm);
        } catch (error) {
          await persist(id, offlinePatch(false, error));
          return;
        }
      }

      const readyNote = await persist(id, { body, status: 'ready', error: null });
      if (!readyNote) return;

      await persist(id, { status: 'syncing', error: null });

      try {
        await uploadNote({ ...readyNote, body, status: 'syncing' });
        await persist(id, { body, status: 'synced', error: null });
      } catch (error) {
        await persist(id, offlinePatch(true, error));
      }
    } finally {
      inflight.delete(id);
      processingCount.value = Math.max(0, processingCount.value - 1);
    }
  }

  async function persist(id: string, partial: Partial<Note>): Promise<Note | null> {
    const index = notes.value.findIndex((note) => note.id === id);
    const current = notes.value[index];
    if (!current) return null;

    const next: Note = { ...current, ...partial, updatedAt: new Date().toISOString() };
    notes.value[index] = next;
    await saveNote(next);
    return next;
  }

  return {
    notes,
    ready,
    online,
    processing,
    pendingNotes,
    pendingCount,
    hydrate,
    watchNetwork,
    addRecording,
    syncPending,
    retry,
    remove,
    playback,
  };
});

function offlinePatch(hasText: boolean, error: unknown): Pick<Note, 'status' | 'error'> {
  if (!navigator.onLine) {
    const status: NoteStatus = hasText ? 'ready' : 'recorded';
    return { status, error: 'Нет сети. Заметка останется на устройстве.' };
  }

  return { status: hasText ? 'ready' : 'failed', error: errorMessage(error) };
}

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useNotesStore, import.meta.hot));
}
