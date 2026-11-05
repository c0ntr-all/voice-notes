import type { RouteRecordRaw } from 'vue-router';

declare module 'vue-router' {
  interface RouteMeta {
    title?: string;
  }
}

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('@/layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'notes',
        component: () => import('@/pages/NotesPage.vue'),
        meta: { title: 'Заметки' },
      },
      {
        path: 'record',
        name: 'record',
        component: () => import('@/pages/RecordPage.vue'),
        meta: { title: 'Запись' },
      },
      {
        path: 'outbox',
        name: 'outbox',
        component: () => import('@/pages/OutboxPage.vue'),
        meta: { title: 'Очередь' },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('@/pages/SettingsPage.vue'),
        meta: { title: 'Настройки' },
      },
      {
        path: 'notes/:id',
        name: 'note',
        component: () => import('@/pages/NoteDetailPage.vue'),
        meta: { title: 'Заметка' },
      },
    ],
  },
  {
    path: '/:catchAll(.*)*',
    component: () => import('@/pages/ErrorNotFound.vue'),
  },
];

export default routes;
