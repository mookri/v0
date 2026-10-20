export const PRODUCT_ID = 'partner' as const;

export const partnerConfig = {
  product: PRODUCT_ID,
  domains: {
    production: 'https://partner.navdhya.com',
    staging: 'https://partner-staging.navdhya.com',
    local: 'http://partner.navdhya.local:3002',
  },
} as const;

export const PARTNER_ROUTES = {
  home: { id: 'partner.home', path: '/' },
  assignments: { id: 'partner.assignments', path: '/assignments' },
  assignmentDetail: { id: 'partner.assignment.detail', path: '/assignments/[assignmentId]' },
  calendar: { id: 'partner.calendar', path: '/calendar' },
  availability: { id: 'partner.availability', path: '/availability' },
  onboarding: { id: 'partner.onboarding', path: '/onboarding' },
  profile: { id: 'partner.profile', path: '/profile' },
  earnings: { id: 'partner.earnings', path: '/earnings' },
} as const;
