<template>
  <q-page class="page-wrap">
    <div class="page-intro">
      <p class="kicker">Синхронизация</p>
      <h1>Очередь</h1>
      <p>
        Здесь всё, что ещё не лежит на сервере: ждёт сеть, распознавание или повторную отправку.
      </p>
    </div>

    <div v-if="pendingNotes.length > 0" class="queue-bar">
      <span>{{ pendingNotes.length }} в очереди</span>
      <q-btn
        unelevated
        color="accent"
        no-caps
        label="Отправить сейчас"
        :loading="processing"
        :disable="!online"
        @click="sync"
      />
    </div>

    <div v-if="!ready" class="page-loading">
      <q-spinner size="32px" color="accent" />
    </div>

    <EmptyState
      v-else-if="pendingNotes.length === 0"
      icon="cloud_done"
      title="Очередь пуста"
      text="Распознанные заметки уже отправлены. Новые без сети появятся здесь сами."
    />

    <div v-else class="note-list">
      <NoteCard v-for="note in pendingNotes" :key="note.id" :note="note" />
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import EmptyState from '@/components/EmptyState.vue';
import NoteCard from '@/components/NoteCard.vue';
import { useNotesStore } from '@/stores/notes-store';

const store = useNotesStore();
const { pendingNotes, ready, processing, online } = storeToRefs(store);

function sync(): void {
  void store.syncPending();
}
</script>
