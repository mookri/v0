"use client";

import type { ReactNode } from "react";
import { AdProvider as AdThemeProvider } from "@/components/ad-network/Adprovider";
import { AdRuntimeProvider } from "@/components/ad-network/runtime/AdRuntimeProvider";
import { executiveTheme } from "@/components/ad-network/Adtokens";

export function AdProvider({ children }: { children: ReactNode }) {
  return (
    <AdRuntimeProvider>
      <AdThemeProvider theme={executiveTheme}>
        {children}
      </AdThemeProvider>
    </AdRuntimeProvider>
  );
}
