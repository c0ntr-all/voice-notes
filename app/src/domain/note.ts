export type NoteStatus = 'recorded' | 'transcribing' | 'ready' | 'syncing' | 'synced' | 'failed';

export interface Note {
  id: string;
  body: string;
  recordedAt: string;
  durationMs: number;
  status: NoteStatus;
  error: string | null;
  hasAudio: boolean;
  updatedAt: string;
}

export interface NoteDraft {
  client_uuid: string;
  body: string;
  recorded_at: string;
  duration_ms: number;
}

export function createNote(durationMs: number): Note {
  const now = new Date().toISOString();

  return {
    id: crypto.randomUUID(),
    body: '',
    recordedAt: now,
    durationMs,
    status: 'recorded',
    error: null,
    hasAudio: true,
    updatedAt: now,
  };
}

export function toDraft(note: Note): NoteDraft {
  return {
    client_uuid: note.id,
    body: note.body,
    recorded_at: note.recordedAt,
    duration_ms: note.durationMs,
  };
}

export function statusLabel(status: NoteStatus): string {
  const labels: Record<NoteStatus, string> = {
    recorded: 'Ждёт сеть',
    transcribing: 'Распознаётся',
    ready: 'Ждёт отправки',
    syncing: 'Отправляется',
    synced: 'На сервере',
    failed: 'Ошибка',
  };

  return labels[status];
}
