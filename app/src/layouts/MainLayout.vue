<template>
  <q-layout view="lHh Lpr lFf" class="app-shell">
    <q-header class="app-header">
      <q-toolbar>
        <q-btn
          class="lt-md"
          flat
          dense
          round
          icon="menu"
          aria-label="Меню"
          @click="drawer = !drawer"
        />
        <q-toolbar-title>{{ title }}</q-toolbar-title>
        <q-spinner v-if="processing" size="18px" color="accent" />
        <div class="connection" :class="{ 'connection--offline': !online }">
          <q-icon :name="online ? 'cloud_done' : 'cloud_off'" size="18px" />
          <span class="gt-xs">{{ online ? 'На связи' : 'Нет сети' }}</span>
        </div>
      </q-toolbar>
    </q-header>

    <q-drawer v-model="drawer" show-if-above class="app-drawer" :width="280">
      <div class="drawer-brand">
        <div class="drawer-mark" aria-hidden="true">В</div>
        <div>
          <div class="drawer-title">Голосовые заметки</div>
          <div class="drawer-caption">Фраза становится текстом</div>
        </div>
      </div>

      <q-list>
        <q-item
          v-for="item in navItems"
          :key="item.to"
          clickable
          exact
          :to="item.to"
          :class="{ 'nav-item--active': isActive(item) }"
          active-class="nav-item--active"
        >
          <q-item-section avatar>
            <q-icon :name="item.icon" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ item.label }}</q-item-label>
            <q-item-label caption>{{ item.caption }}</q-item-label>
          </q-item-section>
          <q-item-section v-if="item.to === '/outbox' && pendingCount > 0" side>
            <q-badge color="accent" :label="pendingCount" />
          </q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { useQuasar } from 'quasar';
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { navItems, type NavItem } from '@/domain/navigation';
import { useNotesStore } from '@/stores/notes-store';

const route = useRoute();
const quasar = useQuasar();
const drawer = ref(false);
const store = useNotesStore();
const { online, pendingCount, processing } = storeToRefs(store);

const title = computed(() => route.meta.title ?? 'Голосовые заметки');

watch(
  () => route.fullPath,
  () => {
    document.title = `${title.value} · Голосовые заметки`;
    if (quasar.screen.lt.md) drawer.value = false;
  },
  { immediate: true },
);

function isActive(item: NavItem): boolean {
  if (item.to === '/') return route.path === '/' || route.path.startsWith('/notes/');
  return route.path === item.to;
}
</script>
