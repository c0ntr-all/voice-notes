<template>
  <span class="status-badge" :class="`status-badge--${status}`">{{ statusLabel(status) }}</span>
</template>

<script setup lang="ts">
import { statusLabel, type OutboxStatus } from '@/domain/note';

defineProps<{
  status: OutboxStatus;
}>();
</script>
