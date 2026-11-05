<template>
  <q-page class="page-wrap">
    <div class="page-intro">
      <p class="kicker">Синхронизация</p>
      <h1>Очередь</h1>
      <p>
        Здесь записи, которые сервер ещё не принял. После отправки текст появится в списке заметок.
      </p>
    </div>

    <div v-if="outbox.length > 0" class="queue-bar">
      <span>{{ outbox.length }} в очереди</span>
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
      v-else-if="outbox.length === 0"
      icon="cloud_done"
      title="Очередь пуста"
      text="Неотправленных записей нет. Новая фраза без сети появится здесь сама."
    />

    <div v-else class="note-list">
      <OutboxCard v-for="recording in outbox" :key="recording.id" :recording="recording" />
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import EmptyState from '@/components/EmptyState.vue';
import OutboxCard from '@/components/OutboxCard.vue';
import { useNotesStore } from '@/stores/notes-store';

const store = useNotesStore();
const { outbox, ready, processing, online } = storeToRefs(store);

function sync(): void {
  void store.syncPending();
}
</script>
