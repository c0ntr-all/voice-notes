<template>
  <router-link class="note-card" :to="`/notes/${recording.id}`">
    <div class="note-card__meta">
      <NoteStatusBadge :status="recording.status" />
      <span>{{ formatDuration(recording.durationMs) }}</span>
    </div>
    <p class="note-card__text">Запись ещё не на сервере</p>
    <div class="note-card__foot">
      <time :datetime="recording.recordedAt">{{ formatRecordedAt(recording.recordedAt) }}</time>
      <span v-if="recording.error">{{ recording.error }}</span>
    </div>
  </router-link>
</template>

<script setup lang="ts">
import NoteStatusBadge from '@/components/NoteStatusBadge.vue';
import { formatDuration, formatRecordedAt } from '@/domain/format';
import type { OutboxRecording } from '@/domain/note';

defineProps<{
  recording: OutboxRecording;
}>();
</script>
