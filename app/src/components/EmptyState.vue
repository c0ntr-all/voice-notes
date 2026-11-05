<template>
  <div class="empty-state">
    <div class="empty-state__mark" aria-hidden="true">
      <q-icon :name="icon" size="28px" />
    </div>
    <h2>{{ title }}</h2>
    <p>{{ text }}</p>
    <slot />
  </div>
</template>

<script setup lang="ts">
defineProps<{
  icon: string;
  title: string;
  text: string;
}>();
</script>
