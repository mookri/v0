import React from 'react';
import Link from 'next/link';
import Image from 'next/image';
import { 
  ArrowLeft, 
  Bell, 
  Settings as SettingsIcon,
  ChevronRight,
  Edit2,
  Crown,
  Wallet,
  Tag,
  Heart,
  Clock,
  User,
  BellRing,
  ShieldCheck,
  Calendar,
  History,
  CreditCard,
  Receipt
} from 'lucide-react';
import { PiFlowerLotus } from "react-icons/pi";

export default function SettingsPage() {
  return (
    <div className="min-h-screen bg-[#FDFDFD] text-gray-900 font-sans pb-24">
      
      {/* Top Header */}
      <div className="px-4 py-4 flex items-center justify-between sticky top-0 bg-[#FDFDFD]/90 backdrop-blur-md z-10">
        <div className="flex items-center gap-3">
          <Link href="/" className="p-1 -ml-1 rounded-full hover:bg-gray-100 transition">
            <ArrowLeft className="w-5 h-5 text-gray-800" strokeWidth={2} />
          </Link>
          <h1 className="text-[22px] font-semibold text-[#1A2530] font-playfair tracking-tight">Settings</h1>
        </div>
        <div className="flex items-center gap-3">
          <button className="relative w-9 h-9 flex items-center justify-center rounded-full bg-[#F5F2EE] hover:bg-[#EBE5DB] transition">
            <Bell className="w-[18px] h-[18px] text-[#1A2530]" strokeWidth={2.2} />
            <span className="absolute top-[8px] right-[8px] w-2 h-2 bg-[#FF3B30] rounded-full border border-white" />
          </button>
          <button className="w-9 h-9 flex items-center justify-center rounded-full bg-[#F5F2EE] hover:bg-[#EBE5DB] transition">
            <SettingsIcon className="w-[18px] h-[18px] text-[#1A2530]" strokeWidth={2.2} />
          </button>
        </div>
      </div>

      <div className="px-4 space-y-5">
        
        {/* Profile Card */}
        <div className="relative w-full rounded-[20px] overflow-hidden bg-[#0A3026] shadow-sm mt-1">
          {/* Background Image Layer */}
          <div className="absolute inset-0 right-0 w-full h-full opacity-60 mix-blend-luminosity">
            <div className="absolute inset-0 bg-gradient-to-r from-[#0A3026] via-[#0A3026]/90 to-transparent z-10" />
            <Image 
              src="/traditional-hindu-puja-mandap.jpg" 
              alt="Temple Background" 
              fill
              className="object-cover object-right"
              priority
            />
          </div>

          <div className="relative z-20 p-5 flex items-center justify-between">
            <div className="flex items-center gap-4">
              {/* Avatar */}
              <div className="relative">
                <div className="w-[76px] h-[76px] rounded-full bg-[#FEF4E3] border-2 border-[#DDA853] shadow-md flex items-center justify-center">
                  <span className="text-[32px] text-[#A67C00] font-medium font-playfair">V</span>
                </div>
                {/* Edit Icon Badge */}
                <div className="absolute bottom-0 right-0 w-6 h-6 bg-white rounded-full border border-gray-200 shadow-sm flex items-center justify-center">
                  <Edit2 className="w-3 h-3 text-gray-600" />
                </div>
              </div>

              {/* User Details */}
              <div className="flex flex-col">
                <h2 className="text-[22px] font-bold text-white font-playfair leading-tight mb-0.5">Victor</h2>
                <span className="text-[13px] text-white/80 font-light tracking-wide mb-0.5">victor@example.com</span>
                <span className="text-[13px] text-white/80 font-light tracking-wide mb-2.5">+91 98765 43210</span>
                
                {/* Gold Member Badge inside card */}
                <div className="flex items-center gap-2">
                  <div className="w-6 h-6 rounded-full bg-[#E5B55C] flex items-center justify-center shadow-sm">
                    <Crown className="w-3.5 h-3.5 text-black" strokeWidth={2.5} />
                  </div>
                  <div className="flex flex-col">
                    <span className="text-[#E5B55C] text-[13px] font-semibold leading-none">Gold Member</span>
                    <span className="text-white/60 text-[10px] mt-0.5">Member since Jan 2024</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Right Chevron */}
            <div className="flex items-center text-white/60 pl-2">
              <ChevronRight className="w-5 h-5 -mr-2" strokeWidth={2} />
              <ChevronRight className="w-5 h-5" strokeWidth={2} />
            </div>
          </div>
        </div>

        {/* Four Quick Stats Cards */}
        <div className="flex gap-2.5 overflow-x-auto no-scrollbar snap-x pb-1 -mx-4 px-4">
          
          <div className="min-w-[110px] bg-white rounded-2xl p-3.5 shadow-[0_2px_10px_rgba(0,0,0,0.03)] border border-[#F0F0F0] snap-start flex-1 relative">
            <Wallet className="w-5 h-5 text-[#E5B55C] mb-3" strokeWidth={2} />
            <div className="text-[11px] text-gray-800 font-semibold">Wallet Balance</div>
            <div className="text-[15px] font-bold text-[#1A2530] mt-0.5">₹0</div>
            <ChevronRight className="w-3.5 h-3.5 text-gray-400 absolute bottom-3.5 right-3.5" />
          </div>

          <div className="min-w-[110px] bg-white rounded-2xl p-3.5 shadow-[0_2px_10px_rgba(0,0,0,0.03)] border border-[#F0F0F0] snap-start flex-1 relative">
            <Tag className="w-5 h-5 text-[#FF5A5F] mb-3" strokeWidth={2} />
            <div className="text-[11px] text-gray-800 font-semibold">My Coupons</div>
            <div className="text-[12px] font-bold text-[#E5B55C] mt-0.5">5 active</div>
            <ChevronRight className="w-3.5 h-3.5 text-gray-400 absolute bottom-3.5 right-3.5" />
          </div>

          <div className="min-w-[110px] bg-white rounded-2xl p-3.5 shadow-[0_2px_10px_rgba(0,0,0,0.03)] border border-[#F0F0F0] snap-start flex-1 relative">
            <Heart className="w-5 h-5 text-[#FF5A5F] mb-3" strokeWidth={2} />
            <div className="text-[11px] text-gray-800 font-semibold">Saved Items</div>
            <div className="text-[13px] font-bold text-[#1A2530] mt-0.5">12 saved</div>
            <ChevronRight className="w-3.5 h-3.5 text-gray-400 absolute bottom-3.5 right-3.5" />
          </div>

          <div className="min-w-[110px] bg-white rounded-2xl p-3.5 shadow-[0_2px_10px_rgba(0,0,0,0.03)] border border-[#F0F0F0] snap-start flex-1 relative">
            <Clock className="w-5 h-5 text-[#5F8B71] mb-3" strokeWidth={2} />
            <div className="text-[11px] text-gray-800 font-semibold">Recent Views</div>
            <div className="text-[13px] font-bold text-[#1A2530] mt-0.5">8 pujas</div>
            <ChevronRight className="w-3.5 h-3.5 text-gray-400 absolute bottom-3.5 right-3.5" />
          </div>

        </div>

        {/* Gold Member Banner */}
        <div className="relative w-full rounded-2xl bg-gradient-to-r from-[#FDE8C0] to-[#E5C282] p-4 flex items-center justify-between shadow-sm overflow-hidden">
          {/* Mocked subtle mandala overlay */}
          <div className="absolute right-0 bottom-0 w-32 h-32 bg-white/20 rounded-full blur-xl mix-blend-overlay pointer-events-none" />
          <div className="absolute right-10 top-2 w-16 h-16 border-2 border-white/10 rounded-full mix-blend-overlay pointer-events-none" />
          
          <div className="relative z-10 flex items-center gap-3">
            <div className="w-[42px] h-[42px] rounded-full bg-[#1A2530] flex items-center justify-center shadow-md">
              <Crown className="w-5 h-5 text-[#E5B55C]" strokeWidth={2.5} />
            </div>
            <div className="flex flex-col">
              <span className="text-[#1A2530] text-[15px] font-bold tracking-tight mb-0.5">You're a Gold Member</span>
              <span className="text-[#1A2530]/70 text-[10px] leading-tight max-w-[140px]">Enjoy exclusive offers, priority booking and special festival benefits.</span>
            </div>
          </div>
          <button className="relative z-10 flex items-center gap-1.5 px-3 py-2 bg-white/90 backdrop-blur-sm rounded-full shadow-sm hover:bg-white transition text-[#1A2530] whitespace-nowrap text-xs font-bold">
            View Benefits <ChevronRight className="w-3.5 h-3.5" strokeWidth={2.5} />
          </button>
        </div>

        {/* Sections */}
        <div className="space-y-6 pt-2">
          
          {/* Account & Preferences */}
          <div>
            <div className="flex items-end justify-between mb-3 px-1">
              <h2 className="text-[19px] font-bold text-[#1A2530] font-playfair tracking-tight">Account & Preferences</h2>
              <span className="text-[11px] text-gray-400 font-medium mb-1">Personalise your experience</span>
            </div>
            <div className="bg-white rounded-[20px] shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-[#F3F4F6] overflow-hidden">
              <div className="divide-y divide-[#F9FAFB] p-2">
                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#1A2530] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <User className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Edit Profile</span>
                      <span className="text-[11px] text-gray-400">Update your personal details</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>

                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#1A2530] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <PiFlowerLotus className="w-5 h-5" />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Preferences</span>
                      <span className="text-[11px] text-gray-400">Language, appearance and app settings</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>

                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#1A2530] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <BellRing className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Notifications</span>
                      <span className="text-[11px] text-gray-400">Manage puja reminders, offers and updates</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>

                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#1A2530] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <ShieldCheck className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Privacy & Security</span>
                      <span className="text-[11px] text-gray-400">Manage your data, security and permissions</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>
              </div>
            </div>
          </div>

          {/* Bookings & Activity */}
          <div>
            <div className="flex items-end justify-between mb-3 px-1">
              <h2 className="text-[19px] font-bold text-[#1A2530] font-playfair tracking-tight">Bookings & Activity</h2>
              <span className="text-[11px] text-gray-400 font-medium mb-1">Your puja journey</span>
            </div>
            <div className="bg-white rounded-[20px] shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-[#F3F4F6] overflow-hidden">
              <div className="divide-y divide-[#F9FAFB] p-2">
                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#0A3026] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <Calendar className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">My Bookings</span>
                      <span className="text-[11px] text-gray-400">View upcoming and past ceremonies</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>

                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#0A3026] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <History className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Ritual History</span>
                      <span className="text-[11px] text-gray-400">Track pujas and services you've completed</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>

                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#0A3026] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <Heart className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Favourites</span>
                      <span className="text-[11px] text-gray-400">Saved pujas, pandits and items</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>
              </div>
            </div>
          </div>

          {/* Payments & Accounts */}
          <div>
            <div className="flex items-end justify-between mb-3 px-1">
              <h2 className="text-[19px] font-bold text-[#1A2530] font-playfair tracking-tight">Payments & Accounts</h2>
              <span className="text-[11px] text-gray-400 font-medium mb-1">Manage your transactions</span>
            </div>
            <div className="bg-white rounded-[20px] shadow-[0_4px_20px_rgba(0,0,0,0.03)] border border-[#F3F4F6] overflow-hidden">
              <div className="divide-y divide-[#F9FAFB] p-2">
                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#1A2530] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <CreditCard className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Payment Methods</span>
                      <span className="text-[11px] text-gray-400">Manage cards, UPI and wallets</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>

                <Link href="#" className="flex items-center justify-between p-3 hover:bg-[#FAFAFA] rounded-xl transition-colors group">
                  <div className="flex items-center gap-4">
                    <div className="w-10 h-10 rounded-full bg-[#F4F6F5] flex items-center justify-center text-[#1A2530] group-hover:bg-white group-hover:shadow-sm transition-all">
                      <Receipt className="w-5 h-5" strokeWidth={1.5} />
                    </div>
                    <div className="flex flex-col">
                      <span className="text-[15px] font-semibold text-[#1A2530] mb-0.5">Invoices & Bills</span>
                      <span className="text-[11px] text-gray-400">View and download your invoices</span>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4 text-gray-300" />
                </Link>
              </div>
            </div>
          </div>

        </div>

      </div>

      {/* Mock Bottom Navigation (matches the screenshot) */}
      <div className="fixed bottom-0 left-0 right-0 h-20 bg-white border-t border-gray-100 flex items-center justify-around px-2 z-20 pb-4 shadow-[0_-4px_20px_rgba(0,0,0,0.02)]">
        <Link href="/" className="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-gray-900 transition">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
          <span className="text-[10px] font-medium">Home</span>
        </Link>
        <Link href="/pujas" className="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-gray-900 transition">
          <PiFlowerLotus className="w-6 h-6" />
          <span className="text-[10px] font-medium">Pujas</span>
        </Link>
        <Link href="/bookings" className="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-gray-900 transition">
          <Calendar className="w-[22px] h-[22px]" strokeWidth={2} />
          <span className="text-[10px] font-medium">Bookings</span>
        </Link>
        <Link href="/pandits" className="flex flex-col items-center justify-center w-16 h-full gap-1 text-gray-400 hover:text-gray-900 transition">
          <User className="w-[22px] h-[22px]" strokeWidth={2} />
          <span className="text-[10px] font-medium">Pandits</span>
        </Link>
        <Link href="/settings" className="flex flex-col items-center justify-center w-16 h-full gap-1 text-[#0A3026] transition">
          <div className="w-[22px] h-[22px] rounded-full border-2 border-[#0A3026] overflow-hidden bg-gray-100">
            <User className="w-5 h-5 text-[#0A3026] mt-0.5 -ml-0.5" strokeWidth={2} />
          </div>
          <span className="text-[10px] font-medium">Profile</span>
        </Link>
      </div>
      
    </div>
  );
}
