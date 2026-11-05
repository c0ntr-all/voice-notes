<template>
  <q-page class="page-wrap">
    <q-pull-to-refresh @refresh="refresh">
      <div class="page-intro">
        <p class="kicker">Библиотека</p>
        <h1>Заметки</h1>
        <p>
          Текст появляется здесь, когда сервер обработает запись. Новые фразы без сети ждут в
          очереди.
        </p>
      </div>

      <p v-if="libraryError" class="record-error" role="alert">{{ libraryError }}</p>

      <q-input
        v-if="notes.length > 0"
        v-model="query"
        class="search-field"
        dense
        outlined
        clearable
        placeholder="Найти в тексте"
        aria-label="Найти в тексте"
      >
        <template #prepend>
          <q-icon name="search" />
        </template>
      </q-input>

      <div v-if="!ready" class="page-loading">
        <q-spinner size="32px" color="accent" />
      </div>

      <EmptyState
        v-else-if="notes.length === 0"
        icon="mic"
        title="Пока пусто"
        :text="
          pendingCount > 0
            ? 'Записи уже лежат в очереди. Текст появится здесь после обработки на сервере.'
            : 'Первая заметка начинается с одной фразы. Без сети запись подождёт на устройстве.'
        "
      >
        <q-btn
          v-if="pendingCount > 0"
          unelevated
          color="accent"
          label="Открыть очередь"
          to="/outbox"
          no-caps
        />
        <q-btn v-else unelevated color="accent" label="Записать" to="/record" no-caps />
      </EmptyState>

      <EmptyState
        v-else-if="groups.length === 0"
        icon="search_off"
        title="Ничего не нашлось"
        text="Попробуйте другое слово или очистите поиск."
      />

      <section v-for="group in groups" :key="group.label" class="note-group">
        <h2>{{ group.label }}</h2>
        <div class="note-list">
          <NoteCard v-for="note in group.notes" :key="note.id" :note="note" />
        </div>
      </section>
    </q-pull-to-refresh>

    <q-page-sticky position="bottom-right" :offset="[18, 18]">
      <q-btn fab icon="mic" color="accent" to="/record" aria-label="Новая запись" />
    </q-page-sticky>
  </q-page>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed, ref } from 'vue';
import EmptyState from '@/components/EmptyState.vue';
import NoteCard from '@/components/NoteCard.vue';
import { groupNotes } from '@/domain/format';
import { useNotesStore } from '@/stores/notes-store';

const store = useNotesStore();
const { notes, ready, libraryError, pendingCount } = storeToRefs(store);
const query = ref<string | null>('');

const groups = computed(() => {
  const needle = (query.value ?? '').trim().toLowerCase();
  const visible = needle
    ? notes.value.filter((note) => note.body.toLowerCase().includes(needle))
    : notes.value;

  return groupNotes(visible);
});

async function refresh(done: () => void): Promise<void> {
  await store.syncPending();
  done();
}
</script>
