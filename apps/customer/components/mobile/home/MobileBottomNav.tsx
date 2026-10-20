import React from 'react';
import Link from 'next/link';
import { Home, Calendar, Users, User } from 'lucide-react';
import Image from 'next/image';

export default function MobileBottomNav() {
  return (
    <div className="fixed bottom-0 left-0 right-0 w-full h-[72px] bg-[#023020] rounded-t-[1.5rem] flex items-center justify-around px-2 z-50 shadow-[0_-8px_30px_rgb(0,0,0,0.15)] pb-safe">
      <Link href="/" className="flex flex-col items-center justify-center gap-1 w-16 h-full pt-1">
        <Home size={22} className="text-white" strokeWidth={2} />
        <span className="text-[10px] font-bold text-white tracking-wide">Home</span>
      </Link>

      <Link href="/bookings" className="flex flex-col items-center justify-center gap-1 w-16 h-full pt-1">
        <Calendar size={22} className="text-gray-400" strokeWidth={2} />
        <span className="text-[10px] font-bold text-gray-400 tracking-wide">Bookings</span>
      </Link>

      {/* Floating Action Button */}
      <div className="relative -top-6">
        <Link
          href="/book/start"
          aria-label="Book a puja"
          className="w-16 h-16 bg-[#023020] rounded-full flex items-center justify-center border-2 border-white/20 shadow-lg relative z-10 overflow-hidden group"
        >
           <div className="absolute inset-0 border-[1.5px] border-[#e8a846] rounded-full scale-90"></div>
           <div className="relative w-8 h-8">
             <Image 
               src="/navdhya-logo.png" 
               alt="Logo" 
               fill 
               className="object-contain"
             />
           </div>
        </Link>
      </div>

      <Link href="/temples" className="flex flex-col items-center justify-center gap-1 w-16 h-full pt-1">
        <Users size={22} className="text-gray-400" strokeWidth={2} />
        <span className="text-[10px] font-bold text-gray-400 tracking-wide">Community</span>
      </Link>

      <Link href="/profile" className="flex flex-col items-center justify-center gap-1 w-16 h-full pt-1">
        <User size={22} className="text-gray-400" strokeWidth={2} />
        <span className="text-[10px] font-bold text-gray-400 tracking-wide">Profile</span>
      </Link>
    </div>
  );
}
