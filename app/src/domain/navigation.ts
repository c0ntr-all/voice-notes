export interface NavItem {
  label: string;
  caption: string;
  icon: string;
  to: string;
}

export const navItems: NavItem[] = [
  { label: 'Заметки', caption: 'Все записи', icon: 'notes', to: '/' },
  { label: 'Запись', caption: 'Новая заметка', icon: 'mic', to: '/record' },
  { label: 'Очередь', caption: 'Ждут отправки', icon: 'cloud_upload', to: '/outbox' },
  { label: 'Настройки', caption: 'Сервер', icon: 'tune', to: '/settings' },
];
