// /components/ui/NavLink.tsx
"use client"

import Link, { LinkProps } from "next/link"
import { useRouter } from "next/navigation"
import React from "react"
import { useLoading } from "@navdhya/design-system/src/context/LoadingContext"

type NavLinkProps = LinkProps & {
  children?: React.ReactNode
  triggerLoading?: boolean // whether clicking sets loading
}

/**
 * NavLink -> wraps Next.js Link and triggers loading.start immediately onClick for instant feedback.
 * Use it for header/nav/button links to avoid click ambiguity.
 */
export function NavLink({ children, triggerLoading = true, ...props }: NavLinkProps) {
  const { start } = useLoading()
  const router = useRouter()

  // Defensive click handler: starts loading and then calls router push if href is string
  const handleClick = (e: React.MouseEvent) => {
    if (!triggerLoading) return
    // Start a named token which will be auto-done by useNavigationLoader when pathname changes.
    start("navlink")
    // Allow Link to handle navigation; if you use router.push manually, ensure to call it here.
  }

  return (
    // @ts-ignore Next Link accepts legacy <a> children etc.
    <Link {...props} onClick={handleClick}>
      {children}
    </Link>
  )
}
