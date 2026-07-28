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
    .id('find-pandit-page')
    .path('/find-pandit')
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
    
  // Auth pages (new simplified URLs)
  PolicyBuilder.create()
    .id('auth-login')
    .path('/login')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-signup')
    .path('/signup')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-forgot-password')
    .path('/forgot-password')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-reset-password')
    .path('/reset-password')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-new-password')
    .path('/new-password')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-two-factor')
    .path('/two-factor')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-lock')
    .path('/lock')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-pin-login')
    .path('/pin-login')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-email-verified')
    .path('/email-verified')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-account-deactivated')
    .path('/account-deactivated')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-verify')
    .path('/verify')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-select-role')
    .path('/select-role')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-complete-profile')
    .path('/complete-profile')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-invite')
    .path('/invite')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-apply-pandit')
    .path('/apply/pandit')
    .use('AuthShell')
    .priority(20)
    .build(),
  PolicyBuilder.create()
    .id('auth-apply-vendor')
    .path('/apply/vendor')
    .use('AuthShell')
    .priority(20)
    .build(),
    
  // Dashboard pages (role-based at root level)
  PolicyBuilder.create()
    .id('user-pages')
    .path('/user/*')
    .use('DashboardShell')
    .priority(20)
    .build(),
    
  PolicyBuilder.create()
    .id('pandit-pages')
    .path('/pandit/*')
    .use('DashboardShell')
    .priority(20)
    .build(),
    
  PolicyBuilder.create()
    .id('vendor-pages')
    .path('/vendor/*')
    .role('Vendor')
    .use('DashboardShell')
    .priority(30)
    .build(),
    
  PolicyBuilder.create()
    .id('manager-pages')
    .path('/manager/*')
    .use('DashboardShell')
    .priority(20)
    .build(),

  // Legacy dashboard pages (redirect)
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

  // SuperAdmin platform pages
  PolicyBuilder.create()
    .id('platform-pages')
    .path('/platform/*')
    .role('SuperAdmin')
    .use('DashboardShell')
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