// lib/auth/mock-session-client.ts
'use client'

import type { Role } from '@/lib/dlo-x/types'
import { DEV_ROLE_COOKIE, isValidDevRole } from '@/lib/auth/cookie-names'

function getCookie(name: string): string | undefined {
  if (typeof document === 'undefined') return undefined
  const value = document.cookie.split('; ').find(row => row.startsWith(`${name}=`))
  return value?.split('=')[1]
}

export function getMockSessionSync() {
  const cookieRole = getCookie(DEV_ROLE_COOKIE)
  const role: Role = isValidDevRole(cookieRole) ? cookieRole : 'User'

  return {
    userId: `mock-${role.toLowerCase()}`,
    email: `${role.toLowerCase()}@navdhya.dev`,
    role,
    isAuthenticated: true,
    profileCompleted: true,
    verificationStatus: role === 'Pandit' ? 'verified' as const : 'pending' as const,
  }
}
