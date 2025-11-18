// components/dlo-x/ErrorBoundary.tsx
'use client'

import React, { Component, ErrorInfo, ReactNode } from 'react';
import { TelemetryService } from '@/lib/dlo-x/telemetry';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error?: Error;
}

export class ErrorBoundary extends Component<Props, State> {
  public state: State = {
    hasError: false
  };

  public static getDerivedStateFromError(error: Error): State {
    // Update state so the next render will show the fallback UI
    return { hasError: true, error };
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo) {
    console.error('DLO-X ErrorBoundary caught an error:', error, errorInfo);
    
    // Send error to telemetry
    TelemetryService.track('error_boundary.caught', {
      error: error.message,
      stack: error.stack,
      componentStack: errorInfo.componentStack
    });
  }

  public render() {
    if (this.state.hasError) {
      // Render fallback UI or default error message
      return this.props.fallback || (
        <div className="min-h-screen flex items-center justify-center bg-background">
          <div className="text-center p-8 bg-card rounded-lg border border-border max-w-md">
            <h2 className="text-xl font-bold text-foreground mb-2">Something went wrong</h2>
            <p className="text-muted-foreground mb-4">
              We're sorry, but something went wrong. Please try refreshing the page.
            </p>
            <button
              className="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
              onClick={() => window.location.reload()}
            >
              Refresh Page
            </button>
          </div>
        </div>
      );
    }

    return this.props.children;
  }
}