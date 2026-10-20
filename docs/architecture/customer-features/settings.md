# Customer feature: settings

Route: `customer.settings` (`/settings`)
Sections by owner: profile→user, language→i18n, notifications→notification, security→identity. No giant settings file.
Analytics: `customer.settings.viewed`, `customer.settings.updated`.
Migration state: IN_PROGRESS (only customer-owned settings; admin/MFA stay out).
