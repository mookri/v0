'use client';

import { useEffect, useState } from 'react';
import { customerApi } from '@/adapters/backend/customer-api-client';

export function NotificationBadge() {
  const [count, setCount] = useState(0);

  const fetchCount = async () => {
    try {
      const data = await customerApi.getUnreadCount();
      setCount(data.count ?? 0);
    } catch {
      // Silent fail - badge stays at 0
    }
  };

  useEffect(() => {
    fetchCount();
    const interval = setInterval(fetchCount, 30_000);
    return () => clearInterval(interval);
  }, []);

  if (count === 0) return null;

  return (
    <span
      className="inline-flex items-center justify-center w-5 h-5 min-w-[1.25rem] px-1 text-xs font-semibold text-white bg-red-600 rounded-full"
      aria-label={count === 1 ? '1 unread notification' : `${count} unread notifications`}
    >
      {count > 99 ? '99+' : count}
    </span>
  );
}