export const appConfig = {
  notesApiUrl: import.meta.env.QCLI_NOTES_API_URL ?? '',
  notesApiToken: import.meta.env.QCLI_NOTES_API_TOKEN ?? '',
};

/**
 * В dev абсолютный адрес из env превращается в /backend/..., чтобы обойти CORS.
 * В сборке остаётся адрес из QCLI_NOTES_API_URL.
 */
export function notesRequestUrl(): string {
  return devProxyUrl(appConfig.notesApiUrl);
}

/** Записи уходят на /api/recordings рядом со списком заметок. */
export function recordingsRequestUrl(): string {
  return siblingApiUrl(notesRequestUrl(), 'recordings');
}

export function recordingsApiUrl(): string {
  return siblingApiUrl(appConfig.notesApiUrl.trim(), 'recordings');
}

function devProxyUrl(configured: string): string {
  const value = configured.trim();
  if (!value || !import.meta.env.QUASAR_DEV || value.startsWith('/')) return value;

  try {
    const url = new URL(value);
    return `/backend${url.pathname}${url.search}`;
  } catch {
    return value;
  }
}

function siblingApiUrl(notesUrl: string, name: string): string {
  const notes = notesUrl.trim().replace(/\/$/, '');
  if (!notes.endsWith('/notes')) return '';
  return `${notes.slice(0, -'notes'.length)}${name}`;
}
