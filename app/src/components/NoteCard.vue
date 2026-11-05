<template>
  <router-link class="note-card" :to="`/notes/${note.id}`">
    <div class="note-card__meta">
      <time :datetime="note.recordedAt">{{ formatRecordedAt(note.recordedAt) }}</time>
      <span>{{ formatDuration(note.durationMs) }}</span>
    </div>
    <p class="note-card__text">{{ note.body }}</p>
  </router-link>
</template>

<script setup lang="ts">
import { formatDuration, formatRecordedAt } from '@/domain/format';
import type { ServerNote } from '@/domain/note';

defineProps<{
  note: ServerNote;
}>();
</script>
