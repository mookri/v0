# PROD-ENV: Production Environment (CI/CD + Vault Sync)

**Target:** All production secrets injected at deploy time via GitHub Actions + Vault
**Model:** Hybrid — GitHub Environments for runtime config, Vault for secrets, sync via OIDC

---

## 1. GitHub Environments (Settings → Environments)

| Environment | Protection Rules | Secrets Source |
|---|---|---|
| `production` | ✅ Required reviewers (Platform team)<br>✅ Wait timer: 5m<br>✅ Deployment branches: `main` only | Vault (via OIDC) + GitHub Environment secrets |
| `staging` | ✅ Required reviewers (1)<br>✅ Deployment branches: `main`, `release/*` | Vault + GitHub Environment secrets |
| `development` | ❌ No protection<br>✅ Deployment branches: `*` | Vault (dev path) |

---

## 2. GitHub Actions OIDC → Vault Auth

### 2.1 Vault JWT Auth Config (already in VAULT-001)

```hcl
vault write auth/jwt/config \
  oidc_discovery_url="https://token.actions.githubusercontent.com" \
  bound_issuer="https://token.actions.githubusercontent.com"
```

### 2.2 GitHub Actions Workflow (`.github/workflows/deploy-prod.yml`)

```yaml
name: Deploy Production

on:
  push:
    branches: [main]
  workflow_dispatch:
    inputs:
      version:
        description: 'Docker image tag'
        required: true

permissions:
  id-token: write      # Required for OIDC
  contents: read       # For checkout
  actions: read        # For reading workflow run

env:
  VAULT_ADDR: https://vault.navdhya.internal:8200
  VAULT_ROLE: github-actions-prod
  AWS_REGION: ap-south-1
  ECR_REGISTRY: ${{ secrets.ECR_REGISTRY }}
  ECS_CLUSTER: navdhya-prod
  ECS_SERVICE: platform-navdhya

jobs:
  vault-secrets:
    name: Fetch Secrets from Vault
    runs-on: ubuntu-latest
    outputs:
      app_key: ${{ steps.vault.outputs.app_key }}
      db_password: ${{ steps.vault.outputs.db_password }}
      redis_auth: ${{ steps.vault.outputs.redis_auth }}
      sendgrid_key: ${{ steps.vault.outputs.sendgrid_key }}
      jwt_secret: ${{ steps.vault.outputs.jwt_secret }}
      # ... all secrets needed at runtime
    steps:
      - name: Configure AWS credentials
        uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::123456789012:role/GitHubActionsVaultAccess
          aws-region: ${{ env.AWS_REGION }}

      - name: Authenticate to Vault via OIDC
        id: vault-auth
        uses: hashicorp/vault-action@v2
        with:
          url: ${{ env.VAULT_ADDR }}
          method: jwt
          role: ${{ env.VAULT_ROLE }}
          # Token auto-exported as VAULT_TOKEN

      - name: Read production secrets
        id: vault
        run: |
          echo "app_key=$(vault kv get -field=app_key secret/prod/app)" >> $GITHUB_OUTPUT
          echo "db_password=$(vault kv get -field=password secret/prod/db)" >> $GITHUB_OUTPUT
          echo "redis_auth=$(vault kv get -field=auth_token secret/prod/redis)" >> $GITHUB_OUTPUT
          echo "sendgrid_key=$(vault kv get -field=api_key secret/prod/sendgrid/transactional)" >> $GITHUB_OUTPUT
          echo "jwt_secret=$(vault kv get -field=secret secret/prod/jwt)" >> $GITHUB_OUTPUT

  build:
    name: Build & Push Docker Image
    needs: vault-secrets
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Configure AWS
        uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::123456789012:role/GitHubActionsECRPush
          aws-region: ${{ env.AWS_REGION }}

      - name: Login to ECR
        run: aws ecr get-login-password --region ${{ env.AWS_REGION }} | docker login --username AWS --password-stdin ${{ env.ECR_REGISTRY }}

      - name: Build image
        run: |
          docker build \
            --build-arg APP_KEY="${{ needs.vault-secrets.outputs.app_key }}" \
            --build-arg DB_PASSWORD="${{ needs.vault-secrets.outputs.db_password }}" \
            --build-arg REDIS_PASSWORD="${{ needs.vault-secrets.outputs.redis_auth }}" \
            --build-arg SENDGRID_API_KEY="${{ needs.vault-secrets.outputs.sendgrid_key }}" \
            --build-arg JWT_SECRET="${{ needs.vault-secrets.outputs.jwt_secret }}" \
            -t ${{ env.ECR_REGISTRY }}/platform-navdhya:${{ github.event.inputs.version || github.sha }} \
            -f docker/Dockerfile.prod .

      - name: Push image
        run: docker push ${{ env.ECR_REGISTRY }}/platform-navdhya:${{ github.event.inputs.version || github.sha }}

  deploy:
    name: Deploy to ECS
    needs: [vault-secrets, build]
    runs-on: ubuntu-latest
    environment: production
    steps:
      - name: Configure AWS
        uses: aws-actions/configure-aws-credentials@v4
        with:
          role-to-assume: arn:aws:iam::123456789012:role/GitHubActionsECSDeploy
          aws-region: ${{ env.AWS_REGION }}

      - name: Update ECS service
        run: |
          aws ecs update-service \
            --cluster ${{ env.ECS_CLUSTER }} \
            --service ${{ env.ECS_SERVICE }} \
            --force-new-deployment \
            --region ${{ env.AWS_REGION }}

      - name: Wait for stability
        run: |
          aws ecs wait services-stable \
            --cluster ${{ env.ECS_CLUSTER }} \
            --services ${{ env.ECS_SERVICE }} \
            --region ${{ env.AWS_REGION }}

      - name: Run migrations
        run: |
          aws ecs run-task \
            --cluster ${{ env.ECS_CLUSTER }} \
            --task-definition platform-navdhya-migrate \
            --launch-type FARGATE \
            --network-configuration "awsvpcConfiguration={subnets=[subnet-xxx,subnet-yyy],securityGroups=[sg-zzz],assignPublicIp=DISABLED}" \
            --region ${{ env.AWS_REGION }}
```

---

## 3. AWS IAM Roles for GitHub Actions

### 3.1 Vault Access Role

```hcl
resource "aws_iam_role" "github_actions_vault" {
  name = "GitHubActionsVaultAccess"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Principal = { Federated = "arn:aws:iam::123456789012:oidc-provider/token.actions.githubusercontent.com" }
      Action = "sts:AssumeRoleWithWebIdentity"
      Condition = {
        StringLike = {
          "token.actions.githubusercontent.com:sub" = "repo:navdhya/platform-navdhya:*"
        }
        StringEquals = {
          "token.actions.githubusercontent.com:aud" = "sts.amazonaws.com"
        }
      }
    }]
  })
}

resource "aws_iam_policy" "github_actions_vault" {
  name = "GitHubActionsVaultPolicy"
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "secretsmanager:GetSecretValue"
      ]
      Resource = [
        "arn:aws:secretsmanager:ap-south-1:123456789012:secret:navdhya/prod/vault/*"
      ]
    }]
  })
}

resource "aws_iam_role_policy_attachment" "github_actions_vault" {
  role       = aws_iam_role.github_actions_vault.name
  policy_arn = aws_iam_policy.github_actions_vault.arn
}
```

### 3.2 ECR Push Role

```hcl
resource "aws_iam_role" "github_actions_ecr" {
  name = "GitHubActionsECRPush"
  assume_role_policy = jsonencode({ ... same OIDC trust ... })
}

resource "aws_iam_policy" "github_actions_ecr" {
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "ecr:GetAuthorizationToken",
        "ecr:BatchCheckLayerAvailability",
        "ecr:PutImage",
        "ecr:InitiateLayerUpload",
        "ecr:UploadLayerPart",
        "ecr:CompleteLayerUpload"
      ]
      Resource = "*"
    }]
  })
}
```

### 3.3 ECS Deploy Role

```hcl
resource "aws_iam_role" "github_actions_ecs" {
  name = "GitHubActionsECSDeploy"
  assume_role_policy = jsonencode({ ... same OIDC trust ... })
}

resource "aws_iam_policy" "github_actions_ecs" {
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect = "Allow"
      Action = [
        "ecs:UpdateService",
        "ecs:DescribeServices",
        "ecs:RunTask",
        "ecs:DescribeTasks",
        "ecs:WaitServicesStable",
        "ecs:ListTasks",
        "iam:PassRole"
      ]
      Resource = [
        "arn:aws:ecs:ap-south-1:123456789012:cluster/navdhya-prod",
        "arn:aws:ecs:ap-south-1:123456789012:service/navdhya-prod/*",
        "arn:aws:ecs:ap-south-1:123456789012:task-definition/navdhya-prod*",
        "arn:aws:iam::123456789012:role/ecsTaskExecutionRole"
      ]
    }]
  })
}
```

---

## 4. GitHub Environment Secrets (Non-Vault)

| Environment | Secret | Value | Source |
|---|---|---|---|
| `production` | `ECR_REGISTRY` | `123456789012.dkr.ecr.ap-south-1.amazonaws.com` | Static |
| `production` | `ECS_CLUSTER` | `navdhya-prod` | Static |
| `production` | `ECS_SERVICE` | `platform-navdhya` | Static |
| `production` | `AWS_REGION` | `ap-south-1` | Static |
| `staging` | `ECR_REGISTRY` | `...` | Static |
| `staging` | `ECS_CLUSTER` | `navdhya-staging` | Static |

---

## 5. Laravel Runtime Config (Injected via Docker Build Args)

```dockerfile
# docker/Dockerfile.prod
FROM php:8.3-fpm-alpine

ARG APP_KEY
ARG DB_PASSWORD
ARG REDIS_PASSWORD
ARG SENDGRID_API_KEY
ARG JWT_SECRET

ENV APP_KEY=${APP_KEY}
ENV DB_PASSWORD=${DB_PASSWORD}
ENV REDIS_PASSWORD=${REDIS_PASSWORD}
ENV SENDGRID_API_KEY=${SENDGRID_API_KEY}
ENV JWT_SECRET=${JWT_SECRET}

# ... rest of Dockerfile
```

**Runtime .env (generated at container start via entrypoint):**

```bash
#!/bin/sh
# docker/entrypoint.prod.sh
cat > /var/www/html/.env <<EOF
APP_ENV=production
APP_DEBUG=false
APP_KEY=${APP_KEY}
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST}
DB_PORT=5432
DB_DATABASE=navdhya_prod
DB_USERNAME=navdhya_app
DB_PASSWORD=${DB_PASSWORD}
REDIS_HOST=${REDIS_HOST}
REDIS_PASSWORD=${REDIS_PASSWORD}
SENDGRID_API_KEY=${SENDGRID_API_KEY}
JWT_SECRET=${JWT_SECRET}
# ... other vars from Vault
EOF

exec php-fpm
```

---

## 6. Vault Sync (Cron Job for Drift Detection)

```yaml
# .github/workflows/vault-sync.yml
name: Vault Drift Detection

on:
  schedule:
    - cron: '0 6 * * *'  # Daily 6 AM UTC
  workflow_dispatch:

jobs:
  drift-check:
    runs-on: ubuntu-latest
    permissions:
      id-token: write
      contents: read
    steps:
      - uses: hashicorp/vault-action@v2
        with:
          url: ${{ secrets.VAULT_ADDR }}
          method: jwt
          role: github-actions-prod

      - name: Compare Vault vs Deployed
        run: |
          # Fetch current deployed secrets from SSM/ECS
          # Compare with Vault
          # Alert on drift via Slack/PagerDuty
```

---

## 7. Rollback Procedure

```bash
# 1. Revert to previous image tag
aws ecs update-service \
  --cluster navdhya-prod \
  --service platform-navdhya \
  --task-definition platform-navdhya:PREVIOUS_TASK_DEF_REVISION \
  --force-new-deployment

# 2. If secrets rotated, revert Vault KV version
vault kv rollback -version=N secret/prod/app

# 3. If DB migration failed, run down migration
aws ecs run-task --task-definition platform-navdhya-migrate-down
```

---

## 7. Monitoring & Alerts

| Check | Tool | Alert |
|---|---|---|
| Workflow success rate | GitHub Actions | < 95% in 24h → PagerDuty |
| Vault token TTL expiry | Vault audit log | Token near expiry → Slack |
| Secret drift | Daily cron | Any drift → PagerDuty |
| Deploy duration | GitHub Actions | > 15min → Slack #platform |

---

## 8. Cost Estimate

| Component | Monthly Cost |
|---|---|
| GitHub Actions (private repo) | Included in org plan |
| Vault OIDC auth requests | Negligible |
| AWS STS AssumeRole calls | ~$0.01/month |
| ECR storage (10 images) | ~$1 |
| **Total** | **~$1/month** |