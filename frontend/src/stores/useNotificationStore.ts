import { create } from 'zustand';
import { userNotificationsApi, UserNotification } from '@/lib/api';

interface NotificationState {
  notifications: UserNotification[];
  unreadCount: number;
  pendingOrdersCount: number;
  isOpen: boolean;
  isLoading: boolean;

  setOpen: (open: boolean) => void;
  fetchNotifications: () => Promise<void>;
  fetchUnreadCount: () => Promise<void>;
  markAsRead: (id: number) => Promise<void>;
  markAllAsRead: () => Promise<void>;
}

export const useNotificationStore = create<NotificationState>()((set, get) => ({
  notifications: [],
  unreadCount: 0,
  pendingOrdersCount: 0,
  isOpen: false,
  isLoading: false,

  setOpen: (open) => {
    set({ isOpen: open });
    if (open && get().notifications.length === 0) {
      get().fetchNotifications();
    }
  },

  fetchNotifications: async () => {
    set({ isLoading: true });
    try {
      const response = await userNotificationsApi.getAll();
      if (response.data) {
        set({
          notifications: response.data.notifications || [],
          unreadCount: response.data.unread_count ?? 0,
          pendingOrdersCount: response.data.pending_orders_count ?? 0,
        });
      }
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    } finally {
      set({ isLoading: false });
    }
  },

  fetchUnreadCount: async () => {
    try {
      const response = await userNotificationsApi.getUnreadCount();
      if (response.data) {
        set({
          unreadCount: response.data.unread_count ?? 0,
          pendingOrdersCount: response.data.pending_orders_count ?? 0,
        });
      }
    } catch (error) {
      console.error('Failed to fetch unread count:', error);
    }
  },

  markAsRead: async (id) => {
    try {
      await userNotificationsApi.markAsRead(id);
      set((state) => ({
        notifications: state.notifications.map((n) =>
          n.id === id ? { ...n, is_read: true, read_at: new Date().toISOString() } : n
        ),
        unreadCount: Math.max(0, state.unreadCount - 1),
      }));
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    }
  },

  markAllAsRead: async () => {
    try {
      await userNotificationsApi.markAllAsRead();
      set((state) => ({
        notifications: state.notifications.map((n) => ({
          ...n,
          is_read: true,
          read_at: n.read_at || new Date().toISOString(),
        })),
        unreadCount: 0,
      }));
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error);
    }
  },
}));
