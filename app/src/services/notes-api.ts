import { notesRequestUrl } from '@/domain/config';
import { toDraft, type Note } from '@/domain/note';

export async function uploadNote(note: Note): Promise<void> {
  const url = notesRequestUrl();
  if (!url) throw new Error('Не задан QCLI_NOTES_API_URL.');

  let response: Response;
  try {
    response = await fetch(url, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'Idempotency-Key': note.id,
      },
      body: JSON.stringify(toDraft(note)),
    });
  } catch {
    throw new Error('Не удалось связаться с сервером.');
  }

  if (response.ok) return;

  throw new Error(await readServerError(response));
}

async function readServerError(response: Response): Promise<string> {
  const raw = (await response.text()).trim();
  if (!raw) return `Сервер ответил ${response.status}.`;

  try {
    const data = JSON.parse(raw) as { message?: string; error?: string };
    const message = data.message || data.error;
    if (message) return `Сервер ответил ${response.status}: ${message}`;
  } catch {
    // Тело не JSON: показываем короткий фрагмент.
  }

  return `Сервер ответил ${response.status}: ${raw.slice(0, 160)}`;
}
