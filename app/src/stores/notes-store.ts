import { acceptHMRUpdate, defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { createOutboxRecording, type OutboxRecording, type ServerNote } from '@/domain/note';
import { errorMessage } from '@/services/errors';
import {
  deleteOutbox,
  deleteLibraryNote,
  getAudio,
  listLibrary,
  listOutbox,
  replaceLibrary,
  saveAudio,
  saveOutbox,
} from '@/services/note-repository';
import { deleteServerNote, fetchNotes, uploadRecording } from '@/services/notes-api';
import { MIN_RECORDING_MS, pcmToWav } from '@/services/pcm';

const OFFLINE_ERROR = 'Нет сети. Запись останется на устройстве.';

export const useNotesStore = defineStore('notes', () => {
  const notes = ref<ServerNote[]>([]);
  const outbox = ref<OutboxRecording[]>([]);
  const deliveredIds = ref<string[]>([]);
  const ready = ref(false);
  const online = ref(typeof navigator === 'undefined' ? true : navigator.onLine);
  const processingCount = ref(0);
  const libraryError = ref('');
  const inflight = new Set<string>();
  let queue: Promise<void> = Promise.resolve();
  let networkBound = false;

  const pendingCount = computed(() => outbox.value.length);
  const processing = computed(() => processingCount.value > 0);

  async function hydrate(): Promise<void> {
    outbox.value = await listOutbox();
    notes.value = await listLibrary();
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

  async function addRecording(pcm: ArrayBuffer, durationMs: number): Promise<OutboxRecording> {
    if (durationMs < MIN_RECORDING_MS) throw new Error('Слишком короткая запись.');

    const recording = createOutboxRecording(durationMs);
    await saveOutbox(recording);
    await saveAudio(recording.id, pcm);
    outbox.value = [recording, ...outbox.value];
    void syncPending();
    return recording;
  }

  function syncPending(): Promise<void> {
    queue = queue.then(async () => {
      const ids = [...outbox.value]
        .sort((left, right) => left.recordedAt.localeCompare(right.recordedAt))
        .map((recording) => recording.id);

      for (const id of ids) await uploadOne(id);
      if (navigator.onLine) await refreshLibrary();
    });

    return queue;
  }

  function retry(id: string): Promise<void> {
    queue = queue.then(async () => {
      const sent = await uploadOne(id);
      if (sent && navigator.onLine) await refreshLibrary();
    });

    return queue;
  }

  async function removeRecording(id: string): Promise<void> {
    await deleteOutbox(id);
    outbox.value = outbox.value.filter((recording) => recording.id !== id);
  }

  async function removeNote(id: string): Promise<void> {
    await deleteServerNote(id);
    await deleteLibraryNote(id);
    notes.value = notes.value.filter((note) => note.id !== id);
  }

  async function playback(id: string): Promise<Blob | null> {
    const pcm = await getAudio(id);
    return pcm ? pcmToWav(pcm) : null;
  }

  async function uploadOne(id: string): Promise<boolean> {
    const current = outbox.value.find((recording) => recording.id === id);
    if (!current || inflight.has(id)) return false;

    if (!navigator.onLine) {
      online.value = false;
      await persist(id, { status: 'pending', error: OFFLINE_ERROR });
      return false;
    }

    inflight.add(id);
    processingCount.value += 1;

    try {
      await persist(id, { status: 'uploading', error: null });
      const pcm = await getAudio(id);
      if (!pcm) throw new Error('Локальная запись не найдена.');

      const latest = outbox.value.find((recording) => recording.id === id) ?? current;
      await uploadRecording(latest, pcm);
      await deleteOutbox(id);
      outbox.value = outbox.value.filter((recording) => recording.id !== id);
      deliveredIds.value = [...deliveredIds.value, id];
      return true;
    } catch (error) {
      await persist(id, failurePatch(error));
      return false;
    } finally {
      inflight.delete(id);
      processingCount.value = Math.max(0, processingCount.value - 1);
    }
  }

  async function refreshLibrary(): Promise<void> {
    if (!navigator.onLine) {
      online.value = false;
      return;
    }

    processingCount.value += 1;

    try {
      const fresh = await fetchNotes();
      notes.value = fresh;
      await replaceLibrary(fresh);
      libraryError.value = '';
    } catch (error) {
      libraryError.value = errorMessage(error);
    } finally {
      processingCount.value = Math.max(0, processingCount.value - 1);
    }
  }

  async function persist(
    id: string,
    partial: Partial<OutboxRecording>,
  ): Promise<OutboxRecording | null> {
    const index = outbox.value.findIndex((recording) => recording.id === id);
    const current = outbox.value[index];
    if (!current) return null;

    const next: OutboxRecording = { ...current, ...partial, updatedAt: new Date().toISOString() };
    outbox.value[index] = next;
    await saveOutbox(next);
    return next;
  }

  return {
    notes,
    outbox,
    deliveredIds,
    ready,
    online,
    processing,
    pendingCount,
    libraryError,
    hydrate,
    watchNetwork,
    addRecording,
    syncPending,
    retry,
    removeRecording,
    removeNote,
    playback,
  };
});

function failurePatch(error: unknown): Pick<OutboxRecording, 'status' | 'error'> {
  if (!navigator.onLine) return { status: 'pending', error: OFFLINE_ERROR };
  return { status: 'failed', error: errorMessage(error) };
}

if (import.meta.hot) {
  import.meta.hot.accept(acceptHMRUpdate(useNotesStore, import.meta.hot));
}
