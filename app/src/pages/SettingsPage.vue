<template>
  <q-page class="page-wrap">
    <div class="page-intro">
      <p class="kicker">Подключение</p>
      <h1>Настройки</h1>
      <p>Ключи читаются из app/.env при сборке. После правки перезапустите dev-сервер.</p>
    </div>

    <section class="settings-card">
      <h2>SpeechKit</h2>
      <dl>
        <div>
          <dt>Доступ</dt>
          <dd>{{ speechKitConfigured() ? 'Ключ задан' : 'Ключ не задан' }}</dd>
        </div>
        <div>
          <dt>Способ</dt>
          <dd>{{ authMode }}</dd>
        </div>
        <div>
          <dt>Каталог</dt>
          <dd>{{ folderId || 'Не задан' }}</dd>
        </div>
        <div>
          <dt>Вызов</dt>
          <dd>{{ speechKitRecognizeUrl() }}</dd>
        </div>
      </dl>
    </section>

    <section class="settings-card">
      <h2>Сервер</h2>
      <dl>
        <div>
          <dt>Эндпоинт</dt>
          <dd>{{ notesApiUrl || 'Не задан' }}</dd>
        </div>
        <div>
          <dt>Запрос</dt>
          <dd>POST JSON: client_uuid, body, recorded_at, duration_ms</dd>
        </div>
      </dl>
    </section>

    <section class="settings-card">
      <h2>Устройство</h2>
      <dl>
        <div>
          <dt>Сеть</dt>
          <dd>{{ online ? 'Есть' : 'Нет' }}</dd>
        </div>
        <div>
          <dt>Заметок</dt>
          <dd>{{ notes.length }}</dd>
        </div>
        <div>
          <dt>В очереди</dt>
          <dd>{{ pendingCount }}</dd>
        </div>
      </dl>
      <q-btn
        class="q-mt-md"
        outline
        color="primary"
        no-caps
        label="Повторить отправку"
        :loading="processing"
        :disable="pendingCount === 0"
        @click="sync"
      />
    </section>
  </q-page>
</template>

<script setup lang="ts">
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { appConfig, speechKitConfigured, speechKitRecognizeUrl } from '@/domain/config';
import { useNotesStore } from '@/stores/notes-store';

const store = useNotesStore();
const { notes, online, pendingCount, processing } = storeToRefs(store);

const folderId = appConfig.folderId.trim();
const notesApiUrl = appConfig.notesApiUrl.trim();
const authMode = computed(() => {
  if (appConfig.speechKitApiKey.trim()) return 'API-ключ';
  if (appConfig.speechKitIamToken.trim()) return 'IAM-токен';
  return 'Не выбран';
});

function sync(): void {
  void store.syncPending();
}
</script>
