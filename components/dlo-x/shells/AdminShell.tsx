// components/dlo-x/shells/AdminShell.tsx
import { ReactNode } from 'react';
import Link from 'next/link';
import { LogOut, Bell, Shield } from 'lucide-react';

interface AdminShellProps {
  children: ReactNode;
}

export function AdminShell({ children }: AdminShellProps) {
  return (
    <div className="min-h-screen bg-background">
      {/* Admin Header */}
      <header className="bg-card border-b border-border">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <Shield className="h-6 w-6 text-red-500 mr-2" />
              <h1 className="text-xl font-serif font-bold text-foreground">Admin Dashboard</h1>
            </div>
            <div className="flex items-center space-x-4">
              <button className="p-2 rounded-full text-muted-foreground hover:text-foreground focus:outline-none">
                <Bell className="h-5 w-5" />
              </button>
              <button className="flex items-center text-sm font-medium text-muted-foreground hover:text-foreground">
                <LogOut className="h-4 w-4 mr-1" />
                Logout
              </button>
            </div>
          </div>
        </div>
      </header>
      
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </div>
    </div>
  );
}