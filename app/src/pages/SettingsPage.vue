<template>
  <q-page class="page-wrap">
    <div class="page-intro">
      <p class="kicker">Подключение</p>
      <h1>Настройки</h1>
      <p>Адрес и токен читаются из app/.env при сборке. После правки перезапустите dev-сервер.</p>
    </div>

    <section class="settings-card">
      <h2>Сервер</h2>
      <dl>
        <div>
          <dt>Заметки</dt>
          <dd>{{ notesApiUrl || 'Не задан' }}</dd>
        </div>
        <div>
          <dt>Записи</dt>
          <dd>{{ recordingsUrl || 'Адрес заметок должен заканчиваться на /notes' }}</dd>
        </div>
        <div>
          <dt>Токен</dt>
          <dd>{{ tokenConfigured ? 'Задан' : 'Не задан' }}</dd>
        </div>
        <div>
          <dt>Отправка</dt>
          <dd>POST WAV: client_uuid, recorded_at, duration_ms, audio</dd>
        </div>
        <div>
          <dt>Список</dt>
          <dd>GET заметок с текстом после обработки на сервере</dd>
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
import { appConfig, recordingsApiUrl } from '@/domain/config';
import { useNotesStore } from '@/stores/notes-store';

const store = useNotesStore();
const { notes, online, pendingCount, processing } = storeToRefs(store);

const notesApiUrl = appConfig.notesApiUrl.trim();
const recordingsUrl = recordingsApiUrl();
const tokenConfigured = Boolean(appConfig.notesApiToken.trim());

function sync(): void {
  void store.syncPending();
}
</script>
