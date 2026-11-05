import { defineBoot } from '#q-app';
import { useNotesStore } from '@/stores/notes-store';

export default defineBoot(async () => {
  if (import.meta.env.QUASAR_SERVER) return;

  const notes = useNotesStore();
  notes.watchNetwork();
  await notes.hydrate();
});
