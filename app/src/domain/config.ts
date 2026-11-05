const SPEECHKIT_DIRECT_URL = 'https://stt.api.cloud.yandex.net/speech/v1/stt:recognize';

export const appConfig = {
  speechKitApiKey: import.meta.env.QCLI_YANDEX_SPEECHKIT_API_KEY ?? '',
  speechKitIamToken: import.meta.env.QCLI_YANDEX_IAM_TOKEN ?? '',
  folderId: import.meta.env.QCLI_YANDEX_FOLDER_ID ?? '',
  notesApiUrl: import.meta.env.QCLI_NOTES_API_URL ?? '',
};

export function speechKitConfigured(): boolean {
  return Boolean(appConfig.speechKitApiKey.trim() || appConfig.speechKitIamToken.trim());
}

export function speechKitAuthorization(): string {
  const apiKey = appConfig.speechKitApiKey.trim();
  if (apiKey) return `Api-Key ${apiKey}`;

  const iamToken = appConfig.speechKitIamToken.trim();
  if (iamToken) return `Bearer ${iamToken}`;

  throw new Error(
    'Не задан доступ к SpeechKit. Добавьте QCLI_YANDEX_SPEECHKIT_API_KEY или QCLI_YANDEX_IAM_TOKEN в app/.env.',
  );
}

/**
 * В dev браузер ходит на тот же origin, а Vite проксирует запрос в SpeechKit.
 * Сборка вызывает Яндекс напрямую: так же будет делать установленное приложение.
 */
export function speechKitRecognizeUrl(): string {
  if (import.meta.env.QUASAR_DEV) return '/yandex-stt/speech/v1/stt:recognize';
  return SPEECHKIT_DIRECT_URL;
}

/**
 * В dev абсолютный адрес из env превращается в /backend/..., чтобы обойти CORS.
 * В сборке остаётся адрес из QCLI_NOTES_API_URL.
 */
export function notesRequestUrl(): string {
  const configured = appConfig.notesApiUrl.trim();
  if (!configured || !import.meta.env.QUASAR_DEV || configured.startsWith('/')) return configured;

  try {
    const url = new URL(configured);
    return `/backend${url.pathname}${url.search}`;
  } catch {
    return configured;
  }
}
