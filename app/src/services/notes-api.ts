import { notesRequestUrl, recordingsRequestUrl, appConfig } from '@/domain/config';
import type { OutboxRecording, ServerNote } from '@/domain/note';
import { pcmToWav } from '@/services/pcm';

interface NotePayload {
  client_uuid: string;
  body: string;
  recorded_at: string;
  duration_ms: number;
  updated_at: string;
}

interface NotesPage {
  data?: NotePayload[];
  meta?: { last_page?: number };
}

export async function uploadRecording(recording: OutboxRecording, pcm: ArrayBuffer): Promise<void> {
  const url = recordingsRequestUrl();
  if (!url) throw new Error('Не задан QCLI_NOTES_API_URL. Адрес должен заканчиваться на /notes.');

  const body = new FormData();
  body.set('client_uuid', recording.id);
  body.set('recorded_at', recording.recordedAt);
  body.set('duration_ms', String(Math.round(recording.durationMs)));
  body.set('audio', pcmToWav(pcm), `${recording.id}.wav`);

  const response = await request(url, {
    method: 'POST',
    headers: apiHeaders({ 'Idempotency-Key': recording.id }),
    body,
  });

  if (!response.ok) throw new Error(await readServerError(response));
}

export async function fetchNotes(): Promise<ServerNote[]> {
  const base = notesRequestUrl();
  if (!base) throw new Error('Не задан QCLI_NOTES_API_URL.');

  const notes: ServerNote[] = [];
  let page = 0;
  let lastPage = 1;

  while (page < lastPage && page < 20) {
    page += 1;
    const url = new URL(base, window.location.origin);
    url.searchParams.set('per_page', '100');
    url.searchParams.set('page', String(page));

    const response = await request(url, { headers: apiHeaders() });
    if (!response.ok) throw new Error(await readServerError(response));

    const payload = (await response.json()) as NotesPage;
    if (!Array.isArray(payload.data)) {
      throw new Error('Сервер прислал список заметок в неожиданном виде.');
    }

    notes.push(...payload.data.map(toServerNote));
    lastPage = payload.meta?.last_page ?? page;
  }

  return notes;
}

export async function deleteServerNote(id: string): Promise<void> {
  const base = notesRequestUrl().replace(/\/$/, '');
  if (!base) throw new Error('Не задан QCLI_NOTES_API_URL.');

  const response = await request(`${base}/${id}`, {
    method: 'DELETE',
    headers: apiHeaders(),
  });

  if (response.status === 404 || response.ok) return;
  throw new Error(await readServerError(response));
}

function toServerNote(item: NotePayload): ServerNote {
  return {
    id: item.client_uuid,
    body: item.body,
    recordedAt: item.recorded_at,
    durationMs: item.duration_ms,
    updatedAt: item.updated_at,
  };
}

function apiHeaders(extra?: HeadersInit): Headers {
  const headers = new Headers(extra);
  headers.set('Accept', 'application/json');

  const token = appConfig.notesApiToken.trim();
  if (token) headers.set('Authorization', `Bearer ${token}`);

  return headers;
}

async function request(url: string | URL, init: RequestInit): Promise<Response> {
  try {
    return await fetch(url, init);
  } catch {
    throw new Error('Не удалось связаться с сервером.');
  }
}

async function readServerError(response: Response): Promise<string> {
  const raw = (await response.text()).trim();
  if (!raw || raw.startsWith('<')) return `Сервер ответил ${response.status}.`;

  try {
    const data = JSON.parse(raw) as {
      message?: string;
      error?: string;
      errors?: Record<string, unknown>;
    };
    const validation = firstValidationError(data.errors);
    if (validation) return validation;

    const message = data.message || data.error;
    if (message) return message;
  } catch {
    // Тело не JSON: показываем короткий фрагмент.
  }

  return `Сервер ответил ${response.status}: ${raw.slice(0, 160)}`;
}

function firstValidationError(errors: Record<string, unknown> | undefined): string {
  if (!errors) return '';

  for (const value of Object.values(errors)) {
    if (Array.isArray(value) && typeof value[0] === 'string' && value[0].trim()) return value[0];
    if (typeof value === 'string' && value.trim()) return value;
  }

  return '';
}
