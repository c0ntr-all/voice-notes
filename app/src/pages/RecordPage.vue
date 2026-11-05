<template>
  <q-page class="record-page">
    <div class="record-panel">
      <p class="kicker">Новая заметка</p>
      <h1>{{ headline }}</h1>
      <p class="record-hint">{{ hint }}</p>

      <div class="record-meter" :style="{ '--progress': progress }">
        <RecordButton :recording="recording" :busy="busy" @toggle="toggle" />
      </div>

      <div class="record-timer" aria-live="polite">{{ formatDuration(elapsedMs) }}</div>
      <p class="record-limit">Лимит SpeechKit — 30 секунд</p>
      <p v-if="error" class="record-error" role="alert">{{ error }}</p>
    </div>
  </q-page>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import RecordButton from '@/components/RecordButton.vue';
import { formatDuration } from '@/domain/format';
import { errorMessage } from '@/services/errors';
import { PcmRecorder } from '@/services/pcm-recorder';
import { MAX_RECORDING_MS } from '@/services/pcm';
import { useNotesStore } from '@/stores/notes-store';

const router = useRouter();
const store = useNotesStore();
const recorder = new PcmRecorder();

const recording = ref(false);
const busy = ref(false);
const elapsedMs = ref(0);
const error = ref('');
let timer = 0;

const progress = computed(() => Math.min(1, elapsedMs.value / MAX_RECORDING_MS));
const headline = computed(() => {
  if (busy.value) return 'Распознаю и отправляю';
  if (recording.value) return 'Слушаю';
  return 'Скажите заметку';
});
const hint = computed(() => {
  if (busy.value)
    return 'Текст появится в заметке. Если сервер недоступен, фраза останется в очереди.';
  if (recording.value) return 'Нажмите ещё раз, чтобы закончить.';
  return 'Одна фраза — одна заметка. Без сети запись подождёт на устройстве.';
});

onMounted(() => {
  window.addEventListener('beforeunload', warnOnLeave);
});

onBeforeUnmount(() => {
  window.removeEventListener('beforeunload', warnOnLeave);
  window.clearInterval(timer);
  recorder.dispose();
});

async function toggle(): Promise<void> {
  error.value = '';
  if (busy.value) return;
  if (recording.value) {
    await finish();
    return;
  }
  await begin();
}

async function begin(): Promise<void> {
  try {
    await recorder.start();
    recording.value = true;
    elapsedMs.value = 0;
    timer = window.setInterval(() => {
      elapsedMs.value = recorder.elapsedMs;
      if (elapsedMs.value >= MAX_RECORDING_MS) void finish();
    }, 200);
  } catch (cause) {
    error.value = errorMessage(cause);
    recorder.dispose();
  }
}

async function finish(): Promise<void> {
  if (!recording.value || busy.value) return;
  busy.value = true;
  recording.value = false;
  window.clearInterval(timer);

  try {
    const recorded = recorder.stop();
    elapsedMs.value = recorded.durationMs;
    const note = await store.addRecording(recorded.pcm, recorded.durationMs);
    await router.push(`/notes/${note.id}`);
  } catch (cause) {
    error.value = errorMessage(cause);
  } finally {
    busy.value = false;
  }
}

function warnOnLeave(event: BeforeUnloadEvent): void {
  if (!recording.value && !busy.value) return;
  event.preventDefault();
}
</script>
