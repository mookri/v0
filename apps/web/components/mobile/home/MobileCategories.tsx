import React from 'react';
import { Flame, UserCircle2, CalendarDays, ShoppingBag, Compass } from 'lucide-react';

const categories = [
  { name: 'Book Puja', icon: Flame, color: 'text-green-800', bg: 'bg-[#0a4b3a]' },
  { name: 'Find Pandit', icon: UserCircle2, color: 'text-[#e8a846]', bg: 'bg-[#fff5e6]' },
  { name: 'Festivals', icon: CalendarDays, color: 'text-[#e8a846]', bg: 'bg-[#fff5e6]' },
  { name: 'Samagri', icon: ShoppingBag, color: 'text-[#e8a846]', bg: 'bg-[#fff5e6]' },
  { name: 'Astrology', icon: Compass, color: 'text-red-700', bg: 'bg-[#ffebe6]' },
];

export default function MobileCategories() {
  return (
    <div className="w-full overflow-x-auto no-scrollbar px-6 pt-2 ">
      <div className="flex items-start justify-between min-w-[max-content] gap-6 pb-2">
        {categories.map((category, index) => {
          const Icon = category.icon;
          // For "Book Puja", it looks like a dark green circle with a yellow flame
          const isFirst = index === 0;
          return (
            <div key={index} className="flex flex-col items-center gap-2 cursor-pointer">
              <div className={`w-14 h-14 rounded-full flex items-center justify-center shadow-sm border border-gray-100 ${category.bg}`}>
                <Icon size={24} className={isFirst ? "text-[#e8a846]" : category.color} strokeWidth={isFirst ? 2 : 1.5} />
              </div>
              <span className="text-[11px] font-semibold text-gray-700 tracking-tight">{category.name}</span>
            </div>
          );
        })}
      </div>
    </div>
  );
}
