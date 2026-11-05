<template>
  <q-page class="page-wrap">
    <div v-if="!ready" class="page-loading">
      <q-spinner size="32px" color="accent" />
    </div>

    <EmptyState
      v-else-if="sent && !note"
      icon="cloud_done"
      title="Запись отправлена"
      text="Текст появится в списке заметок, когда сервер её обработает."
    >
      <q-btn unelevated color="primary" label="К списку" to="/" no-caps />
    </EmptyState>

    <EmptyState
      v-else-if="!note && !recording"
      icon="search_off"
      title="Заметка не найдена"
      text="Она могла быть удалена или ещё не обработана сервером."
    >
      <q-btn unelevated color="primary" label="К списку" to="/" no-caps />
    </EmptyState>

    <article v-else-if="shown" class="note-detail">
      <router-link class="back-link" :to="recording ? '/outbox' : '/'">
        {{ recording ? 'Очередь' : 'Все заметки' }}
      </router-link>
      <div class="note-detail__meta">
        <NoteStatusBadge v-if="recording" :status="recording.status" />
        <span>{{ formatDuration(shown.durationMs) }}</span>
      </div>
      <h1>{{ note?.body || 'Запись без текста' }}</h1>
      <p class="note-detail__time">
        <time :datetime="shown.recordedAt">{{ formatRecordedAt(shown.recordedAt) }}</time>
      </p>
      <p v-if="recording?.error" class="record-error" role="alert">{{ recording.error }}</p>

      <audio v-if="audioUrl" class="note-player" :src="audioUrl" controls />

      <div class="note-actions">
        <q-btn
          v-if="recording"
          unelevated
          color="accent"
          no-caps
          label="Отправить снова"
          :loading="recording.status === 'uploading'"
          :disable="!online"
          @click="retry"
        />
        <q-btn v-if="note" outline color="primary" no-caps label="Скопировать" @click="copy" />
        <q-btn flat color="negative" no-caps label="Удалить" @click="remove" />
      </div>
    </article>
  </q-page>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useQuasar } from 'quasar';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import EmptyState from '@/components/EmptyState.vue';
import NoteStatusBadge from '@/components/NoteStatusBadge.vue';
import { formatDuration, formatRecordedAt } from '@/domain/format';
import { errorMessage } from '@/services/errors';
import { useNotesStore } from '@/stores/notes-store';

const route = useRoute();
const router = useRouter();
const quasar = useQuasar();
const store = useNotesStore();
const { notes, outbox, deliveredIds, ready, online } = storeToRefs(store);
const audioUrl = ref('');

const note = computed(() => notes.value.find((item) => item.id === noteId()));
const recording = computed(() => outbox.value.find((item) => item.id === noteId()));
const shown = computed(() => note.value ?? recording.value ?? null);
const sent = computed(() => deliveredIds.value.includes(noteId()));

watch(recording, () => void loadAudio(), { immediate: true });
onBeforeUnmount(revokeAudio);

async function loadAudio(): Promise<void> {
  revokeAudio();
  const current = recording.value;
  if (!current) return;

  const wav = await store.playback(current.id);
  if (!wav || recording.value?.id !== current.id) return;
  audioUrl.value = URL.createObjectURL(wav);
}

function revokeAudio(): void {
  if (!audioUrl.value) return;
  URL.revokeObjectURL(audioUrl.value);
  audioUrl.value = '';
}

async function retry(): Promise<void> {
  const current = recording.value;
  if (!current) return;
  await store.retry(current.id);
}

async function copy(): Promise<void> {
  const text = note.value?.body;
  if (!text) return;
  try {
    await navigator.clipboard.writeText(text);
    quasar.notify({ message: 'Текст скопирован', color: 'primary', timeout: 1500 });
  } catch {
    quasar.notify({ message: 'Не удалось скопировать текст', color: 'negative' });
  }
}

function remove(): void {
  const currentNote = note.value;
  const currentRecording = recording.value;
  if (!currentNote && !currentRecording) return;

  quasar
    .dialog({
      title: currentRecording ? 'Удалить запись?' : 'Удалить заметку?',
      message: currentRecording
        ? 'Файл пропадёт с устройства и не отправится на сервер.'
        : 'Текст пропадёт на сервере и на этом устройстве.',
      cancel: { label: 'Оставить', flat: true, noCaps: true },
      ok: { label: 'Удалить', color: 'negative', unelevated: true, noCaps: true },
      persistent: true,
    })
    .onOk(() => {
      const action = currentRecording
        ? store.removeRecording(currentRecording.id)
        : store.removeNote(currentNote!.id);

      void action
        .then(() => router.push(currentRecording ? '/outbox' : '/'))
        .catch((cause: unknown) => {
          quasar.notify({ message: errorMessage(cause), color: 'negative' });
        });
    });
}

function noteId(): string {
  const id = route.params.id;
  return typeof id === 'string' ? id : '';
}
</script>
