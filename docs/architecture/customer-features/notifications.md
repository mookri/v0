# Customer feature: notifications

Route: `customer.notifications` (`/notifications`)
Contract: `CustomerNotification{destination?: RouteReference}` — deep links resolve via route IDs, never raw URLs in payloads. Mark-read is a server op (optimistic only with rollback).
Analytics: `customer.notifications.viewed`.
Migration state: IN_PROGRESS (dedicated Notification capability later).
