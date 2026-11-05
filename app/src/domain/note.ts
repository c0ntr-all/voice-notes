export type OutboxStatus = 'pending' | 'uploading' | 'failed';

export interface OutboxRecording {
  id: string;
  recordedAt: string;
  durationMs: number;
  status: OutboxStatus;
  error: string | null;
  updatedAt: string;
}

export interface ServerNote {
  id: string;
  body: string;
  recordedAt: string;
  durationMs: number;
  updatedAt: string;
}

export function createOutboxRecording(durationMs: number): OutboxRecording {
  const now = new Date().toISOString();

  return {
    id: crypto.randomUUID(),
    recordedAt: now,
    durationMs,
    status: 'pending',
    error: null,
    updatedAt: now,
  };
}

export function statusLabel(status: OutboxStatus): string {
  const labels: Record<OutboxStatus, string> = {
    pending: 'Ждёт сеть',
    uploading: 'Отправляется',
    failed: 'Ошибка',
  };

  return labels[status];
}
