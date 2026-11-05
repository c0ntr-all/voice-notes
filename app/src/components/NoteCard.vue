<template>
  <router-link class="note-card" :to="`/notes/${note.id}`">
    <div class="note-card__meta">
      <NoteStatusBadge :status="note.status" />
      <span>{{ formatDuration(note.durationMs) }}</span>
    </div>
    <p class="note-card__text">{{ note.body || 'Текст появится после распознавания' }}</p>
    <div class="note-card__foot">
      <time :datetime="note.recordedAt">{{ formatRecordedAt(note.recordedAt) }}</time>
      <span v-if="note.error">{{ note.error }}</span>
    </div>
  </router-link>
</template>

<script setup lang="ts">
import NoteStatusBadge from '@/components/NoteStatusBadge.vue';
import { formatDuration, formatRecordedAt } from '@/domain/format';
import type { Note } from '@/domain/note';

defineProps<{
  note: Note;
}>();
</script>
