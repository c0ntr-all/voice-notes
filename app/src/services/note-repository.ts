import type { Note } from '@/domain/note';

const DB_NAME = 'voice-notes';
const DB_VERSION = 1;

let database: Promise<IDBDatabase> | null = null;

export async function listNotes(): Promise<Note[]> {
  const notes = await requestStore<Note[]>('notes', 'readonly', (store) => store.getAll());
  return notes.sort((left, right) => right.recordedAt.localeCompare(left.recordedAt));
}

export async function saveNote(note: Note): Promise<void> {
  await requestStore('notes', 'readwrite', (store) => store.put(note));
}

export async function saveAudio(id: string, pcm: ArrayBuffer): Promise<void> {
  await requestStore('audio', 'readwrite', (store) => store.put(pcm, id));
}

export async function getAudio(id: string): Promise<ArrayBuffer | null> {
  const audio = await requestStore<ArrayBuffer | undefined>('audio', 'readonly', (store) =>
    store.get(id),
  );
  return audio ?? null;
}

export async function deleteNote(id: string): Promise<void> {
  const db = await openDatabase();
  await new Promise<void>((resolve, reject) => {
    const transaction = db.transaction(['notes', 'audio'], 'readwrite');
    transaction.objectStore('notes').delete(id);
    transaction.objectStore('audio').delete(id);
    transaction.oncomplete = () => resolve();
    transaction.onerror = () =>
      reject(transaction.error ?? new Error('Не удалось удалить заметку.'));
  });
}

function openDatabase(): Promise<IDBDatabase> {
  database ??= new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onupgradeneeded = () => {
      const db = request.result;
      if (!db.objectStoreNames.contains('notes')) db.createObjectStore('notes', { keyPath: 'id' });
      if (!db.objectStoreNames.contains('audio')) db.createObjectStore('audio');
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () =>
      reject(request.error ?? new Error('Не удалось открыть локальное хранилище.'));
  });

  return database;
}

function requestStore<T>(
  name: 'notes' | 'audio',
  mode: IDBTransactionMode,
  run: (store: IDBObjectStore) => IDBRequest<T>,
): Promise<T> {
  return openDatabase().then(
    (db) =>
      new Promise<T>((resolve, reject) => {
        const transaction = db.transaction(name, mode);
        const request = run(transaction.objectStore(name));
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error ?? new Error('Ошибка локального хранилища.'));
      }),
  );
}
