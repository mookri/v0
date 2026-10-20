/**
 * DNS records for navdhya.com — AWS Route 53 hosted zone.
 *
 * This file is the source of truth for the records that need to be created
 * when the domain is provisioned. It is NOT executed at runtime; the actual
 * zone is managed via `terraform/`, AWS CDK, or the Route 53 console.
 *
 * Plan reference: PR 0.25 / PR 11.5.
 */

export const DOMAIN = "navdhya.com";

/** Apex (root) domain records. */
export const APEX_RECORDS = [
  {
    type: "A",
    name: DOMAIN,
    alias: { target: "d3q8xyz.cloudfront.net.", hostedZoneId: "Z2FDTNDATAQYW2" },
    comment: "CloudFront distribution (PR 11.5)",
  },
  {
    type: "AAAA",
    name: DOMAIN,
    alias: { target: "d3q8xyz.cloudfront.net.", hostedZoneId: "Z2FDTNDATAQYW2" },
    comment: "IPv6 mirror of A record",
  },
] as const;

/** `www.` subdomain. */
export const WWW_RECORDS = [
  {
    type: "CNAME",
    name: `www.${DOMAIN}`,
    value: DOMAIN,
    ttl: 300,
    comment: "www -> apex (CloudFront handles both)",
  },
] as const;

/** Mail / transactional. SES will own the SPF / DKIM records (PR 11.5). */
export const MAIL_RECORDS = [
  {
    type: "MX",
    name: DOMAIN,
    value: [
      "10 inbound-smtp.ap-south-1.amazonaws.com.",
    ],
    ttl: 300,
    comment: "SES inbound MX (PR 11.5)",
  },
  {
    type: "TXT",
    name: DOMAIN,
    value: '"v=spf1 include:amazonses.com -all"',
    ttl: 300,
    comment: "SPF for SES (PR 11.5)",
  },
] as const;

/** ACM certificate validation (auto-records emitted by Route 53). */
export const ACM_VALIDATION_NOTE =
  "ACM-issued certs validate via CNAME records that AWS adds to the zone automatically. Do not pre-create them.";

/** Verification records for third-party services (search engines, etc). */
export const VERIFICATION_RECORDS = [
  // Google Search Console, Bing Webmaster, etc. Add as we onboard.
  // { type: 'TXT', name: 'google-site-verification', value: '...', ttl: 300 },
] as const;

/**
 * Health-check / failover notes (PR 11.5 — DR).
 *  - Route 53 health checks ping /api/healthz every 30s
 *  - ap-south-1 is primary, ap-southeast-1 is failover for the diaspora cluster
 *  - Both regions serve the same CloudFront distribution; health check
 *    triggers origin failover via CloudFront origin groups
 */
export const FAILOVER_NOTE =
  "DR strategy: CloudFront origin group with ap-south-1 (RDS Aurora Global primary) and ap-southeast-1 (read-replica region). Health check via Route 53.";
