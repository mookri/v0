// lib/dlo-x/layout-policies.ts
import { LayoutPolicy } from './types';
import { PolicyBuilder } from './policy-builder';

// Define all layout policies for the application
export const layoutPolicies: LayoutPolicy[] = [
  // Public pages
  PolicyBuilder.create()
    .id('home-page')
    .path('/')
    .use('MainShell')
    .priority(10)
    .build(),
    
  PolicyBuilder.create()
    .id('about-page')
    .path('/about')
    .use('MainShell')
    .priority(10)
    .build(),
    
  PolicyBuilder.create()
    .id('contact-page')
    .path('/contact')
    .use('MainShell')
    .priority(10)
    .build(),
    
  PolicyBuilder.create()
    .id('pujas-page')
    .path('/pujas/*')
    .use('MainShell', 'PoojaShell')
    .priority(10)
    .build(),
    
  PolicyBuilder.create()
    .id('pandits-page')
    .path('/pandits')
    .use('MainShell')
    .priority(10)
    .build(),
    
  PolicyBuilder.create()
    .id('learn-page')
    .path('/learn')
    .use('MainShell')
    .priority(10)
    .build(),
    
  PolicyBuilder.create()
    .id('community-page')
    .path('/community')
    .use('MainShell')
    .priority(10)
    .build(),
    
  PolicyBuilder.create()
    .id('resources-page')
    .path('/resources')
    .use('MainShell')
    .priority(10)
    .build(),
    
  // Auth pages
  PolicyBuilder.create()
    .id('auth-pages')
    .path('/auth/*')
    .use('AuthShell')
    .priority(20)
    .build(),
    
  // Dashboard pages
  PolicyBuilder.create()
    .id('dashboard-pages')
    .path('/dashboard/*')
    .use('DashboardShell')
    .priority(20)
    .build(),
    
  // Admin pages
  PolicyBuilder.create()
    .id('admin-pages')
    .path('/admin/*')
    .role('Admin')
    .use('AdminShell')
    .priority(30)
    .build(),
    
  // Vendor pages
  PolicyBuilder.create()
    .id('vendor-pages')
    .path('/vendor/*')
    .role('Vendor')
    .use('VendorShell')
    .priority(30)
    .build(),
    
  // Mobile pages
  PolicyBuilder.create()
    .id('mobile-pages')
    .path('/m/*')
    .device('mobile')
    .use('MobileShell')
    .priority(25)
    .build(),
    
  // Fallback for all other pages
  PolicyBuilder.create()
    .id('default-page')
    .path('*')
    .use('MainShell')
    .priority(1)
    .build()
];