// components/dlo-x/shells/ChromeShell.tsx
import { ReactNode } from 'react';

interface ChromeShellProps {
  children: ReactNode;
}

export function ChromeShell({ children }: ChromeShellProps) {
  return (
    <div className="min-h-screen bg-background">
      {/* Chrome browser-like interface shell */}
      <div className="border-b border-border bg-card">
        <div className="flex items-center px-4 py-2 space-x-2">
          <div className="flex space-x-1">
            <div className="w-3 h-3 rounded-full bg-red-400"></div>
            <div className="w-3 h-3 rounded-full bg-yellow-400"></div>
            <div className="w-3 h-3 rounded-full bg-green-400"></div>
          </div>
          <div className="flex-grow mx-4">
            <div className="bg-muted rounded-md px-3 py-1 text-xs text-muted-foreground truncate">
              Navdhya Application
            </div>
          </div>
        </div>
      </div>
      
      <main className="flex-grow">
        {children}
      </main>
    </div>
  );
}