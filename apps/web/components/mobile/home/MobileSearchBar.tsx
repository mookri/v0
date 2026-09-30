import React from 'react';
import { Search, SlidersHorizontal } from 'lucide-react';

export default function MobileSearchBar() {
  return (
    <div className="w-full bg-white rounded-full flex items-center px-4 py-3 shadow-[0_8px_30px_rgb(0,0,0,0.12)]">
      <Search size={20} className="text-gray-400" />
      <input 
        type="text" 
        placeholder="Search pujas, pandits, rituals..." 
        className="flex-grow bg-transparent border-none outline-none text-gray-800 placeholder-gray-400 px-3 text-sm"
      />
      <button className="text-gray-500 border-l border-gray-200 pl-3">
        <SlidersHorizontal size={20} />
      </button>
    </div>
  );
}
