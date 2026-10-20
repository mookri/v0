# Provisioning Runbooks (Hybrid Model)

**Decision:** Hybrid — Git remote via provider UI, managed Redis/Mail, self-hosted Vault, prod env via CI/CD secrets.

| Runbook | Target | Provider/Method | Owner |
|---|---|---|---|
| **GIT-001** | Git remote | GitHub (org: navdhya) | Platform |
| **REDIS-001** | Cache/Session | AWS ElastiCache (Redis 7.x) | Platform |
| **MAIL-001** | Transactional email | SendGrid (dedicated IP) | Platform |
| **VAULT-001** | Secrets management | HashiCorp Vault (self-hosted on EC2) | Platform |
| **PROD-ENV** | Production secrets | GitHub Actions Environments + Vault sync | Platform |

---

## Prerequisites

- AWS account with appropriate IAM permissions
- GitHub organization `navdhya` with admin access
- SendGrid account with domain authentication
- HashiCorp Vault binary or Docker image
- Terraform ≥ 1.6 (for infra-as-code where applicable)

---

## Execution Order

1. **GIT-001** — Git remote (prerequisite for CI/CD)
2. **REDIS-001** — Redis (required by Laravel cache/session/queue)
3. **MAIL-001** — Mail (required by OTP, notifications)
4. **VAULT-001** — Vault (stores all other credentials)
5. **PROD-ENV** — Wire CI/CD to Vault + GitHub Environments

Each runbook is idempotent and includes rollback steps.