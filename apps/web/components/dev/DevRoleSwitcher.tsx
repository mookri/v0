'use client'

import { useTransition } from 'react'
import { useRouter } from 'next/navigation'
import type { Role } from '@/lib/dlo-x/types'
import { DEV_ROLE_COOKIE, VALID_DEV_ROLES } from '@/lib/auth/cookie-names'

interface DevRoleSwitcherProps {
  currentRole: Role
}

export function DevRoleSwitcher({ currentRole }: DevRoleSwitcherProps) {
  const router = useRouter()
  const [isPending, startTransition] = useTransition()

  return (
    <label className="flex items-center gap-2 text-xs text-muted-foreground">
      <span className="font-medium uppercase tracking-[0.16em]">Dev role</span>
      <select
        aria-label="Select preview role"
        defaultValue={currentRole}
        className="rounded-md border border-border bg-background px-2 py-1 text-xs text-foreground"
        onChange={(event) => {
          const value = event.target.value as Role
          if (!(VALID_DEV_ROLES as readonly string[]).includes(value)) return
          document.cookie = `${DEV_ROLE_COOKIE}=${value}; path=/; max-age=31536000; samesite=lax`
          startTransition(() => {
            router.refresh()
          })
        }}
      >
        {VALID_DEV_ROLES.map((role) => (
          <option key={role} value={role}>
            {role}
          </option>
        ))}
      </select>
      {isPending ? <span>Updating…</span> : null}
    </label>
  )
}
