# Shell classification (§7.15)

| Current | Type | Target |
|---------|------|--------|
| MainShell | product shell | apps/web PublicWebShell |
| ChromeShell | layout primitive | shared composition helper |
| AuthShell | app-specific shell | identity flow shell (web) |
| DashboardShell | layout primitive | reusable dashboard layout |
| MinimalShell | primitive | fallback shell |
| MobileShell | REMOVE as product | viewport.mode input only |
| PlatformShell | re-evaluate | console shell candidate |
| PoojaShell | domain composition | puja page layout, not shell |
| VendorShell | product shell | apps/partner PartnerShell (future) |
| AdminShell | product shell | apps/admin AdminShell (future) |

Viewport never determines product; role never auto-selects app.
