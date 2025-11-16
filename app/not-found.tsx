'use client';

import Link from 'next/link';
import { Button } from '@/components/ui/button';
import { Home, Search, ArrowLeft } from 'lucide-react';

export default function NotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-background px-4">
      <div className="text-center max-w-md">
        <div className="mb-6">
          <div className="text-6xl font-bold text-primary mb-2">404</div>
          <h1 className="text-2xl font-serif font-bold text-foreground mb-4">
            Page Not Found
          </h1>
          <p className="text-muted-foreground mb-8">
            Sorry, we couldn't find the page you're looking for. It might have been removed, renamed, or didn't exist in the first place.
          </p>
        </div>

        <div className="flex flex-col sm:flex-row gap-3 justify-center">
          <Button asChild>
            <Link href="/" className="flex items-center gap-2">
              <Home size={16} />
              Back to Home
            </Link>
          </Button>
          
          <Button variant="outline" asChild>
            <Link href="/services" className="flex items-center gap-2">
              <Search size={16} />
              Browse Services
            </Link>
          </Button>
        </div>

        <div className="mt-8">
          <Button variant="ghost" asChild className="text-muted-foreground hover:text-foreground">
            <Link href="javascript:history.back()" className="flex items-center gap-2 mx-auto">
              <ArrowLeft size={16} />
              Go Back
            </Link>
          </Button>
        </div>
      </div>
    </div>
  );
}