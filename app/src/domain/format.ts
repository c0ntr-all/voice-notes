import type { Note } from '@/domain/note';

const DAY_MS = 24 * 60 * 60 * 1000;

export function formatDuration(ms: number): string {
  const total = Math.max(0, Math.round(ms / 1000));
  const minutes = Math.floor(total / 60);
  const seconds = total % 60;
  return `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

export function formatRecordedAt(iso: string): string {
  const date = new Date(iso);
  const time = new Intl.DateTimeFormat('ru-RU', { hour: '2-digit', minute: '2-digit' }).format(
    date,
  );
  const day = startOfDay(date);
  const today = startOfDay(new Date());

  if (day === today) return `Сегодня, ${time}`;
  if (day === today - DAY_MS) return `Вчера, ${time}`;

  const full = new Intl.DateTimeFormat('ru-RU', { day: 'numeric', month: 'long' }).format(date);
  return `${full}, ${time}`;
}

export function dayLabel(iso: string): string {
  const date = new Date(iso);
  const day = startOfDay(date);
  const today = startOfDay(new Date());

  if (day === today) return 'Сегодня';
  if (day === today - DAY_MS) return 'Вчера';

  return new Intl.DateTimeFormat('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(date);
}

export function groupNotes(notes: Note[]): { label: string; notes: Note[] }[] {
  const groups = new Map<string, Note[]>();

  for (const note of notes) {
    const label = dayLabel(note.recordedAt);
    const bucket = groups.get(label) ?? [];
    bucket.push(note);
    groups.set(label, bucket);
  }

  return [...groups.entries()].map(([label, grouped]) => ({ label, notes: grouped }));
}

function startOfDay(date: Date): number {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
}
