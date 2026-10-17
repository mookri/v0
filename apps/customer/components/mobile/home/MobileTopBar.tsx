import React from 'react';
import { Bell, User } from 'lucide-react';
import Image from 'next/image';
import Link from 'next/link';

export default function MobileTopBar() {
  // Using a variable to easily toggle authenticated state for demonstration
  const isAuthenticated = false;

  return (
    <header className="absolute top-0 left-0 right-0 z-50 flex items-center justify-between px-5 py-5 pt-6">
      
      {/* Left Side: Logo */}
      <Link href="/" className="flex items-center gap-2.5">
        <div className="relative w-9 h-9">
          <Image 
            src="/navdhya-logo.png" 
            alt="Navdhya Logo" 
            fill 
            className="object-contain"
          />
        </div>
        <div className="flex flex-col">
          <span className="text-[19px] font-bold tracking-wide leading-none text-white drop-shadow-md">
            NAVDHYA
          </span>
          <span className="text-[10px] text-[#e8a846] leading-none mt-0.5 font-medium drop-shadow-sm tracking-wide">
            Sacred by Tradition
          </span>
        </div>
      </Link>

      {/* Right Side: Actions with Circular Backgrounds */}
      <div className="flex items-center gap-3">
        
        {/* Notification Icon */}
        <button 
          aria-label="Notifications"
          className="relative flex items-center justify-center w-10 h-10 rounded-full bg-black/30 backdrop-blur-md border border-white/10 text-white shadow-sm transition-transform active:scale-90"
        >
          <Bell size={20} strokeWidth={2} />
          {/* Unread indicator */}
          <span className="absolute top-2.5 right-2.5 w-2 h-2 bg-[#e8a846] rounded-full border border-[#023020]" />
        </button>

        {/* Auth / Profile Icon */}
        <button 
          aria-label={isAuthenticated ? "Profile" : "Sign In"}
          className="relative flex items-center justify-center w-10 h-10 rounded-full bg-black/30 backdrop-blur-md border border-white/10 text-white shadow-sm transition-transform active:scale-90 overflow-hidden"
        >
          {isAuthenticated ? (
            <Image 
              src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=100&h=100&fit=crop" 
              alt="Profile" 
              fill 
              className="object-cover"
            />
          ) : (
            <User size={20} strokeWidth={2} />
          )}
        </button>

      </div>
    </header>
  );
}
