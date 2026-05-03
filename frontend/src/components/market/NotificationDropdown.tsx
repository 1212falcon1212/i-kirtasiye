"use client";

import { useEffect, useRef, useCallback, useState } from "react";
import { useRouter } from "next/navigation";
import {
  Bell,
  Box,
  Truck,
  PackageCheck,
  XCircle,
  TrendingDown,
  PartyPopper,
  CheckCheck,
  Loader2,
  ShoppingBag,
  Wallet,
  ClipboardCheck,
  AlertTriangle,
} from "lucide-react";
import { Button } from "@/components/ui/button";
import { useNotificationStore } from "@/stores/useNotificationStore";
import { useAuth } from "@/contexts/AuthContext";
import { cn } from "@/lib/utils";
import type { UserNotification } from "@/lib/api";

const NOTIFICATION_ICONS: Record<string, React.ReactNode> = {
  order_created: <ShoppingBag className="w-4 h-4 text-blue-500" />,
  new_order: <ShoppingBag className="w-4 h-4 text-[#ea580c]" />,
  order_confirmed: <Box className="w-4 h-4 text-[#ea580c]" />,
  order_shipped: <Truck className="w-4 h-4 text-blue-500" />,
  order_delivered: <PackageCheck className="w-4 h-4 text-[#ea580c]" />,
  buyer_confirmed: <ClipboardCheck className="w-4 h-4 text-[#ea580c]" />,
  wallet_released: <Wallet className="w-4 h-4 text-[#ea580c]" />,
  order_cancelled: <XCircle className="w-4 h-4 text-red-500" />,
  price_drop: <TrendingDown className="w-4 h-4 text-[#ea580c]" />,
  wishlist_added: <TrendingDown className="w-4 h-4 text-accent-soft0" />,
  welcome: <PartyPopper className="w-4 h-4 text-purple-500" />,
};

function formatTimeAgo(dateStr: string): string {
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMin = Math.floor(diffMs / 60000);
  const diffHour = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHour / 24);

  if (diffMin < 1) return "Az önce";
  if (diffMin < 60) return `${diffMin} dk önce`;
  if (diffHour < 24) return `${diffHour} saat önce`;
  if (diffDay < 7) return `${diffDay} gün önce`;
  return date.toLocaleDateString("tr-TR", { day: "numeric", month: "short" });
}

export function NotificationDropdown() {
  const router = useRouter();
  const { user } = useAuth();
  const {
    notifications,
    unreadCount,
    pendingOrdersCount,
    isOpen,
    isLoading,
    setOpen,
    fetchNotifications,
    fetchUnreadCount,
    markAsRead,
    markAllAsRead,
  } = useNotificationStore();
  const buttonRef = useRef<HTMLButtonElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);
  const [dropdownPos, setDropdownPos] = useState({ top: 0, right: 0 });

  const totalBadge = unreadCount + pendingOrdersCount;

  // Polling for unread count every 60 seconds
  useEffect(() => {
    if (!user) return;

    fetchUnreadCount();

    intervalRef.current = setInterval(() => {
      fetchUnreadCount();
    }, 60000);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [user, fetchUnreadCount]);

  // Calculate fixed position from button
  const updateDropdownPosition = useCallback(() => {
    if (buttonRef.current) {
      const rect = buttonRef.current.getBoundingClientRect();
      setDropdownPos({
        top: rect.bottom + 8,
        right: window.innerWidth - rect.right,
      });
    }
  }, []);

  // Recalculate on scroll/resize while open
  useEffect(() => {
    if (!isOpen) return;
    updateDropdownPosition();
    const handleUpdate = () => updateDropdownPosition();
    window.addEventListener("scroll", handleUpdate, true);
    window.addEventListener("resize", handleUpdate);
    return () => {
      window.removeEventListener("scroll", handleUpdate, true);
      window.removeEventListener("resize", handleUpdate);
    };
  }, [isOpen, updateDropdownPosition]);

  // Close dropdown on outside click
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as Node;
      const isInsideButton = buttonRef.current?.contains(target);
      const isInsideDropdown = dropdownRef.current?.contains(target);
      if (!isInsideButton && !isInsideDropdown) {
        setOpen(false);
      }
    };

    if (isOpen) {
      document.addEventListener("mousedown", handleClickOutside);
    }
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, [isOpen, setOpen]);

  const handleToggle = useCallback(() => {
    const nextOpen = !isOpen;
    setOpen(nextOpen);
    if (nextOpen) {
      fetchNotifications();
    }
  }, [isOpen, setOpen, fetchNotifications]);

  const handleNotificationClick = useCallback(
    (notification: UserNotification) => {
      if (!notification.is_read) {
        markAsRead(notification.id);
      }
      const url = notification.data?.url as string | undefined;
      if (url) {
        router.push(url);
      }
      setOpen(false);
    },
    [markAsRead, router, setOpen]
  );

  const handleMarkAllRead = useCallback(() => {
    markAllAsRead();
  }, [markAllAsRead]);

  return (
    <>
      {/* Bell Button */}
      <button
        ref={buttonRef}
        onClick={handleToggle}
        className="hidden sm:flex items-center text-slate-600 dark:text-slate-300 hover:text-[#ea580c] transition-colors cursor-pointer relative"
      >
        <div className="relative">
          <Bell className={cn("w-[22px] h-[22px]", pendingOrdersCount > 0 && "animate-[wiggle_1s_ease-in-out]")} />
          {totalBadge > 0 && (
            <span className="absolute -top-2 -right-2.5 min-w-[18px] h-[18px] px-1 bg-red-500 rounded-full flex items-center justify-center">
              <span className="text-[10px] font-bold text-white leading-none">
                {totalBadge > 99 ? "99+" : totalBadge}
              </span>
            </span>
          )}
        </div>
      </button>

      {/* Dropdown */}
      {isOpen && (
        <div
          ref={dropdownRef}
          className="fixed w-[380px] max-h-[480px] bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 shadow-lg z-[9999] overflow-hidden flex flex-col"
          style={{ top: dropdownPos.top, right: dropdownPos.right }}
        >
          {/* Header */}
          <div className="flex items-center justify-between px-4 py-3 border-b border-slate-100 dark:border-slate-700">
            <h3 className="text-sm font-semibold text-slate-900 dark:text-white">
              Bildirimler
            </h3>
            {unreadCount > 0 && (
              <button
                onClick={handleMarkAllRead}
                className="flex items-center gap-1 text-xs text-[#ea580c] hover:text-[#ea580c] font-medium transition-colors"
              >
                <CheckCheck className="w-3.5 h-3.5" />
                Tümünü okundu işaretle
              </button>
            )}
          </div>

          {/* Content */}
          <div className="flex-1 overflow-y-auto">
            {/* Persistent Pending Orders Banner */}
            {pendingOrdersCount > 0 && (
              <button
                onClick={() => {
                  router.push('/market/hesabim?tab=siparislerim&sub=sattiklarim');
                  setOpen(false);
                }}
                className="w-full flex items-center gap-3 px-4 py-3 bg-[#ffedd5] dark:bg-[#1e40af]/20 border-b border-[#ffedd5] dark:border-[#c2410c]/30 hover:bg-[#ffedd5] dark:hover:bg-[#1e40af]/30 transition-colors"
              >
                <div className="w-9 h-9 rounded-full bg-[#ffedd5] dark:bg-[#1e40af]/40 flex items-center justify-center flex-shrink-0">
                  <AlertTriangle className="w-4.5 h-4.5 text-[#ea580c] dark:text-[#ffedd5]" />
                </div>
                <div className="flex-1 text-left">
                  <p className="text-sm font-semibold text-[#c2410c] dark:text-[#ffedd5]">
                    {pendingOrdersCount} bekleyen siparisiniz var
                  </p>
                  <p className="text-xs text-[#ea580c] dark:text-[#ffedd5] mt-0.5">
                    Onaylamanız bekleniyor — tıklayarak görüntüleyin
                  </p>
                </div>
                <div className="flex-shrink-0 w-6 h-6 bg-[#1e3a8a] rounded-full flex items-center justify-center">
                  <span className="text-xs font-bold text-white">{pendingOrdersCount}</span>
                </div>
              </button>
            )}

            {isLoading && notifications.length === 0 ? (
              <div className="flex items-center justify-center py-12">
                <Loader2 className="w-6 h-6 text-slate-400 animate-spin" />
              </div>
            ) : notifications.length === 0 && pendingOrdersCount === 0 ? (
              <div className="flex flex-col items-center justify-center py-12 px-4">
                <div className="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-3">
                  <Bell className="w-6 h-6 text-slate-400" />
                </div>
                <p className="text-sm text-slate-500 dark:text-slate-400">
                  Henüz bildiriminiz yok
                </p>
              </div>
            ) : (
              <div className="divide-y divide-slate-100 dark:divide-slate-700">
                {notifications.map((notification) => (
                  <button
                    key={notification.id}
                    onClick={() => handleNotificationClick(notification)}
                    className={cn(
                      "w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors",
                      !notification.is_read && "bg-[#ffedd5]/50 dark:bg-[#1e40af]/10"
                    )}
                  >
                    {/* Unread indicator + Icon */}
                    <div className="relative flex-shrink-0 mt-0.5">
                      <div className="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center">
                        {NOTIFICATION_ICONS[notification.type] || (
                          <Bell className="w-4 h-4 text-slate-400" />
                        )}
                      </div>
                      {!notification.is_read && (
                        <span className="absolute -top-0.5 -left-0.5 w-2.5 h-2.5 bg-[#ffedd5] rounded-full border-2 border-white dark:border-slate-800" />
                      )}
                    </div>

                    {/* Content */}
                    <div className="flex-1 min-w-0">
                      <p
                        className={cn(
                          "text-sm leading-snug",
                          notification.is_read
                            ? "text-slate-600 dark:text-slate-400"
                            : "text-slate-900 dark:text-white font-medium"
                        )}
                      >
                        {notification.title}
                      </p>
                      <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-2">
                        {notification.body}
                      </p>
                      <p className="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
                        {formatTimeAgo(notification.created_at)}
                      </p>
                    </div>
                  </button>
                ))}
              </div>
            )}
          </div>
        </div>
      )}
    </>
  );
}
