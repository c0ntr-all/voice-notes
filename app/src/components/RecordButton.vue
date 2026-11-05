<template>
  <button
    class="record-button"
    :class="{ 'record-button--active': recording, 'record-button--busy': busy }"
    type="button"
    :disabled="busy"
    :aria-pressed="recording"
    :aria-label="recording ? 'Остановить запись' : 'Начать запись'"
    @click="$emit('toggle')"
  >
    <q-spinner v-if="busy" size="36px" color="white" />
    <q-icon v-else :name="recording ? 'stop' : 'mic'" size="42px" />
  </button>
</template>

<script setup lang="ts">
defineProps<{
  recording: boolean;
  busy: boolean;
}>();

defineEmits<{
  toggle: [];
}>();
</script>
