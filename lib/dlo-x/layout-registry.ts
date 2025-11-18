// lib/dlo-x/layout-registry.ts
import { ShellName } from './types';

// Import all shell components
import { MinimalShell } from '../../components/dlo-x/shells/MinimalShell';
import { MainShell } from '../../components/dlo-x/shells/MainShell';
import { AuthShell } from '../../components/dlo-x/shells/AuthShell';
import { DashboardShell } from '../../components/dlo-x/shells/DashboardShell';
import { AdminShell } from '../../components/dlo-x/shells/AdminShell';
import { VendorShell } from '../../components/dlo-x/shells/VendorShell';
import { PoojaShell } from '../../components/dlo-x/shells/PoojaShell';
import { MobileShell } from '../../components/dlo-x/shells/MobileShell';
import { ChromeShell } from '../../components/dlo-x/shells/ChromeShell';

// This registry maps shell names to their actual component implementations
export const layoutRegistry: Record<ShellName, React.ComponentType<any>> = {
  MinimalShell,
  MainShell,
  AuthShell,
  DashboardShell,
  AdminShell,
  VendorShell,
  PoojaShell,
  MobileShell,
  ChromeShell
};

// Function to register a shell component
export function registerShell(name: ShellName, component: React.ComponentType<any>) {
  layoutRegistry[name] = component;
}

// Function to get a shell component
export function getShell(name: ShellName) {
  return layoutRegistry[name];
}