<template>
  <q-page class="page-wrap">
    <div v-if="!ready" class="page-loading">
      <q-spinner size="32px" color="accent" />
    </div>

    <EmptyState
      v-else-if="!note"
      icon="search_off"
      title="Заметка не найдена"
      text="Она могла быть удалена с этого устройства."
    >
      <q-btn unelevated color="primary" label="К списку" to="/" no-caps />
    </EmptyState>

    <article v-else class="note-detail">
      <router-link class="back-link" to="/">Все заметки</router-link>
      <div class="note-detail__meta">
        <NoteStatusBadge :status="note.status" />
        <span>{{ formatDuration(note.durationMs) }}</span>
      </div>
      <h1>{{ note.body || 'Без текста' }}</h1>
      <p class="note-detail__time">
        <time :datetime="note.recordedAt">{{ formatRecordedAt(note.recordedAt) }}</time>
      </p>
      <p v-if="note.error" class="record-error" role="alert">{{ note.error }}</p>

      <audio v-if="audioUrl" class="note-player" :src="audioUrl" controls />

      <div class="note-actions">
        <q-btn
          v-if="note.status !== 'synced'"
          unelevated
          color="accent"
          no-caps
          label="Отправить снова"
          :loading="processing"
          @click="retry"
        />
        <q-btn v-if="note.body" outline color="primary" no-caps label="Скопировать" @click="copy" />
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
import { useNotesStore } from '@/stores/notes-store';

const route = useRoute();
const router = useRouter();
const quasar = useQuasar();
const store = useNotesStore();
const { notes, ready, processing } = storeToRefs(store);
const audioUrl = ref('');

const note = computed(() => notes.value.find((item) => item.id === noteId()));

watch(note, () => void loadAudio(), { immediate: true });
onBeforeUnmount(revokeAudio);

async function loadAudio(): Promise<void> {
  revokeAudio();
  const current = note.value;
  if (!current?.hasAudio) return;

  const wav = await store.playback(current.id);
  if (!wav || note.value?.id !== current.id) return;
  audioUrl.value = URL.createObjectURL(wav);
}

function revokeAudio(): void {
  if (!audioUrl.value) return;
  URL.revokeObjectURL(audioUrl.value);
  audioUrl.value = '';
}

async function retry(): Promise<void> {
  const current = note.value;
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
  const current = note.value;
  if (!current) return;

  quasar
    .dialog({
      title: 'Удалить заметку?',
      message: 'Текст и запись пропадут с этого устройства.',
      cancel: { label: 'Оставить', flat: true, noCaps: true },
      ok: { label: 'Удалить', color: 'negative', unelevated: true, noCaps: true },
      persistent: true,
    })
    .onOk(() => {
      void store.remove(current.id).then(() => router.push('/'));
    });
}

function noteId(): string {
  const id = route.params.id;
  return typeof id === 'string' ? id : '';
}
</script>
