import type { OutboxRecording, ServerNote } from '@/domain/note';

const DB_NAME = 'voice-notes';
const DB_VERSION = 2;

let database: Promise<IDBDatabase> | null = null;

export async function listOutbox(): Promise<OutboxRecording[]> {
  const items = await requestStore<OutboxRecording[]>('outbox', 'readonly', (store) =>
    store.getAll(),
  );
  return items
    .map(normalizeOutbox)
    .sort((left, right) => right.recordedAt.localeCompare(left.recordedAt));
}

export async function saveOutbox(recording: OutboxRecording): Promise<void> {
  await requestStore('outbox', 'readwrite', (store) => store.put(recording));
}

export async function listLibrary(): Promise<ServerNote[]> {
  const notes = await requestStore<ServerNote[]>('library', 'readonly', (store) => store.getAll());
  return notes.sort((left, right) => right.recordedAt.localeCompare(left.recordedAt));
}

export async function replaceLibrary(notes: ServerNote[]): Promise<void> {
  const db = await openDatabase();
  await runTransaction(db, ['library'], (stores) => {
    const library = stores.library;
    if (!library) return;
    library.clear();
    for (const note of notes) library.put(note);
  });
}

export async function deleteLibraryNote(id: string): Promise<void> {
  await requestStore('library', 'readwrite', (store) => store.delete(id));
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

export async function deleteOutbox(id: string): Promise<void> {
  const db = await openDatabase();
  await runTransaction(db, ['outbox', 'audio'], (stores) => {
    stores.outbox?.delete(id);
    stores.audio?.delete(id);
  });
}

function normalizeOutbox(recording: OutboxRecording): OutboxRecording {
  if (recording.status !== 'uploading') return recording;
  return { ...recording, status: 'pending' };
}

function openDatabase(): Promise<IDBDatabase> {
  database ??= new Promise((resolve, reject) => {
    const request = indexedDB.open(DB_NAME, DB_VERSION);

    request.onupgradeneeded = (event) => {
      const db = request.result;
      if (!db.objectStoreNames.contains('audio')) db.createObjectStore('audio');
      if (!db.objectStoreNames.contains('outbox'))
        db.createObjectStore('outbox', { keyPath: 'id' });
      if (!db.objectStoreNames.contains('library')) {
        db.createObjectStore('library', { keyPath: 'id' });
      }

      if (event.oldVersion >= 2 || !db.objectStoreNames.contains('notes')) return;

      const tx = request.transaction;
      if (!tx) return;

      const getAll = tx.objectStore('notes').getAll();
      getAll.onsuccess = () => {
        const outbox = tx.objectStore('outbox');
        const library = tx.objectStore('library');
        const audio = tx.objectStore('audio');

        for (const raw of getAll.result as LegacyNote[]) {
          if (!raw?.id) continue;

          if (raw.status === 'synced') {
            if (raw.body && raw.recordedAt) {
              library.put({
                id: raw.id,
                body: raw.body,
                recordedAt: raw.recordedAt,
                durationMs: raw.durationMs ?? 0,
                updatedAt: raw.updatedAt ?? raw.recordedAt,
              } satisfies ServerNote);
            }
            audio.delete(raw.id);
            continue;
          }

          if (raw.hasAudio === false || !raw.recordedAt) continue;

          outbox.put({
            id: raw.id,
            recordedAt: raw.recordedAt,
            durationMs: raw.durationMs ?? 0,
            status: 'pending',
            error: null,
            updatedAt: raw.updatedAt ?? raw.recordedAt,
          } satisfies OutboxRecording);
        }
      };
    };
    request.onsuccess = () => resolve(request.result);
    request.onerror = () =>
      reject(request.error ?? new Error('Не удалось открыть локальное хранилище.'));
  });

  return database;
}

function requestStore<T>(
  name: StoreName,
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

function runTransaction(
  db: IDBDatabase,
  names: StoreName[],
  run: (stores: Partial<Record<StoreName, IDBObjectStore>>) => void,
): Promise<void> {
  return new Promise((resolve, reject) => {
    const transaction = db.transaction(names, 'readwrite');
    const stores: Partial<Record<StoreName, IDBObjectStore>> = {};
    for (const name of names) stores[name] = transaction.objectStore(name);
    run(stores);
    transaction.oncomplete = () => resolve();
    transaction.onerror = () =>
      reject(transaction.error ?? new Error('Ошибка локального хранилища.'));
  });
}

type StoreName = 'outbox' | 'library' | 'audio';

interface LegacyNote {
  id?: string;
  body?: string;
  recordedAt?: string;
  durationMs?: number;
  status?: string;
  hasAudio?: boolean;
  updatedAt?: string;
}
