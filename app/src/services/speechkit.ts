import { speechKitAuthorization, speechKitRecognizeUrl, appConfig } from '@/domain/config';
import { MAX_PCM_BYTES, SAMPLE_RATE } from '@/services/pcm';

interface SpeechKitPayload {
  result?: string;
  error_message?: string;
  message?: string;
}

export async function recognizeSpeech(pcm: ArrayBuffer): Promise<string> {
  if (pcm.byteLength === 0) throw new Error('Пустая запись.');
  if (pcm.byteLength > MAX_PCM_BYTES) {
    throw new Error('Запись длиннее лимита SpeechKit. Говорите не дольше 30 секунд.');
  }

  const url = new URL(speechKitRecognizeUrl(), window.location.origin);
  url.searchParams.set('lang', 'ru-RU');
  url.searchParams.set('format', 'lpcm');
  url.searchParams.set('sampleRateHertz', String(SAMPLE_RATE));

  const folderId = appConfig.folderId.trim();
  if (folderId) url.searchParams.set('folderId', folderId);

  let response: Response;
  try {
    response = await fetch(url, {
      method: 'POST',
      headers: {
        Authorization: speechKitAuthorization(),
        'Content-Type': 'application/octet-stream',
      },
      body: pcm,
    });
  } catch {
    throw new Error('Не удалось связаться с SpeechKit.');
  }

  const payload = await readPayload(response);
  if (!response.ok) {
    if (response.status === 401 || response.status === 403) {
      throw new Error(payload.message || 'SpeechKit отклонил ключ доступа.');
    }
    throw new Error(payload.message || `SpeechKit ответил ${response.status}.`);
  }

  const text = payload.result.trim();
  if (!text) {
    throw new Error('Речь не распознана. Говорите чуть дольше и ближе к микрофону.');
  }

  return text;
}

async function readPayload(response: Response): Promise<{ result: string; message: string }> {
  const raw = await response.text();

  try {
    const data = JSON.parse(raw) as SpeechKitPayload;
    return {
      result: data.result ?? '',
      message: data.error_message || data.message || '',
    };
  } catch {
    return { result: '', message: raw.slice(0, 180) };
  }
}
