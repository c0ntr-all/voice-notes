<template>
  <span class="status-badge" :class="`status-badge--${status}`">{{ statusLabel(status) }}</span>
</template>

<script setup lang="ts">
import { statusLabel, type NoteStatus } from '@/domain/note';

defineProps<{
  status: NoteStatus;
}>();
</script>
