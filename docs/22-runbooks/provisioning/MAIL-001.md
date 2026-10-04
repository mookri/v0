# MAIL-001: Transactional Email (SendGrid)

**Target:** OTP emails, booking confirmations, notifications, marketing (separate)
**Provider:** SendGrid (dedicated IP for transactional)
**Domain:** `navdhya.com` (subdomain `mail.navdhya.com` for transactional)

---

## 1. SendGrid Account Setup

1. Create account at https://sendgrid.com → Team: `navdhya-platform`
2. Plan: **Pro 100k** (dedicated IP included) or **Premier** for higher volume
3. Verify organization: `Navdhya Ritual Services Pvt Ltd`

---

## 2. Domain Authentication (Settings → Sender Authentication)

| Setting | Value |
|---|---|
| Domain | `mail.navdhya.com` |
| Link Branding | `links.navdhya.com` |
| Reverse DNS | `o1.mail.navdhya.com` (auto) |
| Automated Security | ✅ On (SendGrid manages DKIM/SPF rotation) |

**DNS Records (add to Route53/Cloudflare):**

| Type | Host | Value |
|---|---|---|
| CNAME | `mail` | `u12345.wl.sendgrid.net` |
| CNAME | `links` | `u12345.wl.sendgrid.net` |
| CNAME | `em1234` | `u12345.wl.sendgrid.net` (DKIM1) |
| CNAME | `em1235` | `u12345.wl.sendgrid.net` (DKIM2) |
| TXT | `@` | `v=spf1 include:sendgrid.net ~all` |
| TXT | `_dmarc` | `v=DMARC1; p=quarantine; rua=mailto:dmarc@navdhya.com` |

---

## 3. Dedicated IP Warmup (Pro plan)

| Week | Daily Volume | Notes |
|---|---|---|
| 1 | 500 | OTP only |
| 2 | 2,000 | Add booking confirmations |
| 3 | 5,000 | Add notifications |
| 4 | 10,000 | Full transactional volume |

Monitor: **Stats → IP Reputation** — keep > 95%

---

## 4. API Key (Settings → API Keys)

| Name | Permissions | Rotation |
|---|---|---|
| `navdhya-transactional` | Mail Send: Full Access; All others: None | 90 days |
| `navdhya-marketing` | Marketing Campaigns: Full; Mail Send: None | 90 days |

**Store in Vault:**
- `sendgrid/transactional/api_key` → `SG.xxx`
- `sendgrid/marketing/api_key` → `SG.yyy`

---

## 5. Templates (Dynamic Transactional)

| Template ID | Name | Variables |
|---|---|---|
| `d-otp-verification` | OTP Code | `{{otp_code}}`, `{{expires_minutes}}`, `{{purpose}}` |
| `d-booking-confirmed` | Booking Confirmed | `{{booking_id}}`, `{{puja_name}}`, `{{date}}`, `{{time}}`, `{{pandit_name}}` |
| `d-booking-reminder` | 24h Reminder | `{{booking_id}}`, `{{puja_name}}`, `{{date}}`, `{{time}}` |
| `d-booking-cancelled` | Booking Cancelled | `{{booking_id}}`, `{{refund_amount}}` |
| `d-verification-requested` | Verify Phone/Email | `{{type}}`, `{{contact}}`, `{{otp_code}}` |
| `d-account-deleted` | Account Deleted | `{{name}}`, `{{deleted_at}}` |

**Template Engine:** Handlebars (SendGrid default)
**Test:** Send test email to `test@navdhya.com` from each template

---

## 6. Suppression Management (Settings → Suppression)

| List | Auto-add | Retention |
|---|---|---|
| Bounces | ✅ | 30 days |
| Blocks | ✅ | 30 days |
| Spam Reports | ✅ | 90 days |
| Invalid Emails | ✅ | 30 days |
| Unsubscribes (marketing) | ✅ | Permanent |

**Transactional emails bypass unsubscribe** — use separate IP/subdomain.

---

## 7. Laravel Config (`config/mail.php`)

```php
'mailers' => [
    'sendgrid' => [
        'transport' => 'sendgrid',
        'api_key' => env('SENDGRID_API_KEY'),
    ],
    'sendgrid_marketing' => [
        'transport' => 'sendgrid',
        'api_key' => env('SENDGRID_MARKETING_API_KEY'),
    ],
],

'from' => [
    'address' => env('MAIL_FROM_ADDRESS', 'no-reply@navdhya.com'),
    'name' => env('MAIL_FROM_NAME', 'Navdhya'),
],
```

---

## 8. Environment Variables (Injected from Vault)

| Variable | Vault Path | Use |
|---|---|---|
| `SENDGRID_API_KEY` | `sendgrid/transactional/api_key` | Transactional mailer |
| `SENDGRID_MARKETING_API_KEY` | `sendgrid/marketing/api_key` | Marketing mailer |
| `MAIL_FROM_ADDRESS` | `sendgrid/from_address` | `no-reply@navdhya.com` |
| `MAIL_FROM_NAME` | Static | `Navdhya` |

---

## 9. Webhook (for delivery tracking)

**URL:** `https://api.navdhya.com/webhooks/sendgrid`
**Events:** `delivered`, `bounce`, `blocked`, `spam_report`, `unsubscribe`, `deferred`

**Verify Signature:**
```php
// In webhook controller
$signature = $request->header('X-Twilio-Email-Event-Webhook-Signature');
$timestamp = $request->header('X-Twilio-Email-Event-Webhook-Timestamp');
$payload = $request->getContent();

$expected = hash_hmac('sha256', $timestamp . $payload, env('SENDGRID_WEBHOOK_SECRET'));
if (!hash_equals($expected, $signature)) {
    abort(401, 'Invalid SendGrid signature');
}
```

**Store secret in Vault:** `sendgrid/webhook_secret`

---

## 10. Monitoring & Alerts

| Metric | Threshold | Alert |
|---|---|---|
| Delivery Rate | < 98% | PagerDuty |
| Bounce Rate | > 2% | PagerDuty |
| Spam Report Rate | > 0.1% | PagerDuty |
| Block Rate | > 0.5% | Slack #platform-alerts |
| IP Reputation | < 90 | Immediate investigation |

---

## 11. Rollback

- Revert DNS to previous provider (MX records)
- Rotate API keys immediately: Settings → API Keys → Revoke
- Switch to fallback provider (Mailgun/SES) via feature flag

---

## 12. Cost Estimate (Monthly)

| Plan | Volume | Cost |
|---|---|---|
| Pro 100k | 100,000 emails | $89.95 |
| Dedicated IP | Included | $0 |
| Email Validation | 10k/mo | $15 |
| **Total** | | **~$105/mo** |