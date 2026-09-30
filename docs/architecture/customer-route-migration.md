| Current Web | Responsibility | Customer Target | Owner | Capability | Status | Fallback | Removal Gate |
|-------------|----------------|-----------------|-------|------------|--------|----------|--------------|
| `/user` | dashboard | `/` | Customer | orchestration | IN_PROGRESS | Web `/user` | E2E + rollback proven |
| `/user/bookings` | booking list | `/bookings` | Customer | Booking | IN_PROGRESS | Web list | gate §12.111 |
| `/user/booking/:id` | booking detail | `/bookings/:id` | Customer | Booking/Puja/Pandit/Pricing | IN_PROGRESS | Web detail | gate §12.111 |
| `/settings` | customer prefs | `/settings` | Customer | User | IN_PROGRESS | Web settings | gate §12.111 |
| `/user/profile` | profile | `/profile` | Customer | User | IN_PROGRESS | Web profile | gate §12.111 |
| `/user/notifications` | notifications | `/notifications` | Customer | Notification | IN_PROGRESS | Web | gate §12.111 |
| `/book` | booking entry | split entry | Web + Customer | Booking | NOT_STARTED | Web `/book` | intent handoff |
| `/pujas/[slug]` | discovery | Web | Web | Puja | Remains Web | — | — |

Rule: old Web routes stay live until Customer route + session handoff + rollback are proven. No deletions in Phase 12.
