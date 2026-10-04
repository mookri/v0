# VAULT-001: Self-Hosted HashiCorp Vault

**Target:** Centralized secrets management for all environments
**Provider:** Self-hosted on AWS EC2 (Auto Scaling Group) + RDS PostgreSQL backend
**Version:** Vault 1.15+ (LTS)

---

## 1. Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Vault Cluster (HA)                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │ Vault Node 1│◀─│ Vault Node 2│──▶│ Vault Node 3│          │
│  │  (Active)   │  │ (Standby)   │  │ (Standby)   │          │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘          │
│         │                │                │                   │
│         └────────────────┼────────────────┘                   │
│                          ▼                                    │
│              ┌───────────────────────┐                        │
│              │  RDS PostgreSQL       │                        │
│              │  (Storage Backend)    │                        │
│              │  Multi-AZ, encrypted  │                        │
│              └───────────────────────┘                        │
└─────────────────────────────────────────────────────────────┘
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
         ┌─────────┐ ┌──────────┐ ┌──────────┐
         │  ECS    │ │  GitHub  │ │  Terraform│
         │  Tasks  │ │  Actions │ │  Cloud    │
         └─────────┘ └──────────┘ └──────────┘
```

---

## 2. Infrastructure (Terraform)

### 2.1 VPC & Network

```hcl
module "vault_vpc" {
  source  = "terraform-aws-modules/vpc/aws"
  version = "~> 5.0"

  name = "navdhya-vault-${var.environment}"
  cidr_block = var.vault_vpc_cidr  # e.g., 10.0.0.0/24

  private_subnets = var.vault_private_subnets
  public_subnets  = []

  enable_dns_hostnames = true
  enable_dns_support   = true

  tags = local.common_tags
}
```

### 2.2 RDS PostgreSQL (Storage Backend)

```hcl
resource "aws_db_instance" "vault" {
  identifier             = "navdhya-vault-${var.environment}"
  engine                 = "postgres"
  engine_version         = "15.4"
  instance_class         = var.environment == "prod" ? "db.r6g.large" : "db.t3.micro"
  allocated_storage      = 100
  max_allocated_storage  = 500
  storage_encrypted      = true
  kms_key_id             = aws_kms_key.vault.arn

  db_name                = "vault"
  username               = "vaultadmin"
  password               = random_password.vault_db.result
  port                   = 5432

  vpc_security_group_ids = [aws_security_group.vault_rds.id]
  db_subnet_group_name   = aws_db_subnet_group.vault.name

  backup_retention_period = 30
  backup_window           = "03:00-04:00"
  maintenance_window      = "sun:04:00-sun:05:00"
  multi_az                = var.environment == "prod"
  deletion_protection     = var.environment == "prod"

  tags = local.common_tags
}
```

### 2.3 EC2 Auto Scaling Group (Vault Nodes)

```hcl
resource "aws_launch_template" "vault" {
  name_prefix   = "navdhya-vault-${var.environment}-"
  image_id      = data.aws_ami.amazon_linux_2023.id
  instance_type = "t3.medium"

  vpc_security_group_ids = [aws_security_group.vault.id]
  iam_instance_profile   = aws_iam_instance_profile.vault.arn

  user_data = base64encode(templatefile("${path.module}/user_data.sh.tpl", {
    vault_version = "1.15.2"
    cluster_name  = "navdhya-${var.environment}"
    rds_endpoint  = aws_db_instance.vault.endpoint
    rds_port      = 5432
    kms_key_id    = aws_kms_key.vault.arn
  }))

  tag_specifications {
    resource_type = "instance"
    tags = merge(local.common_tags, { Name = "navdhya-vault-${var.environment}" })
  }
}

resource "aws_autoscaling_group" "vault" {
  name                = "navdhya-vault-${var.environment}"
  launch_template     = { id = aws_launch_template.vault.id, version = "$Latest" }
  min_size            = 3
  max_size            = 3
  desired_capacity    = 3
  vpc_zone_identifier = var.vault_private_subnets

  health_check_type         = "EC2"
  health_check_grace_period = 300

  tag {
    key                 = "Name"
    value               = "navdhya-vault-${var.environment}"
    propagate_at_launch = true
  }
}

resource "aws_lb_target_group" "vault" {
  name        = "navdhya-vault-${var.environment}"
  port        = 8200
  protocol    = "HTTP"
  vpc_id      = var.vpc_id
  target_type = "instance"

  health_check {
    path                = "/v1/sys/health"
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 10
    interval            = 30
    matcher             = "200,429,472,473"  # Active/Standby/PerfStandby
  }
}

resource "aws_lb" "vault" {
  name               = "navdhya-vault-${var.environment}"
  internal           = true
  load_balancer_type = "network"
  subnets            = var.vault_private_subnets
  security_groups    = [aws_security_group.vault_lb.id]
}
```

---

## 3. Vault Configuration (HCL) — `/etc/vault.d/vault.hcl`

```hcl
storage "postgresql" {
  connection_url = "postgresql://vaultadmin:${DB_PASSWORD}@${RDS_ENDPOINT}:5432/vault?sslmode=require"
  table          = "vault_kv_store"
  ha_enabled     = true
}

listener "tcp" {
  address     = "0.0.0.0:8200"
  tls_disable = false
  tls_cert_file = "/opt/vault/tls/vault.crt"
  tls_key_file  = "/opt/vault/tls/vault.key"
}

cluster_addr = "https://${PRIVATE_IP}:8201"
api_addr     = "https://${PRIVATE_IP}:8200"

ui = true

telemetry {
  prometheus_retention_time = "24h"
  disable_hostname          = true
}

seal "awskms" {
  region = "ap-south-1"
  kms_key_id = "${KMS_KEY_ID}"
}
```

---

## 4. User Data Script (`user_data.sh.tpl`)

```bash
#!/bin/bash
set -euo pipefail

VAULT_VERSION="${vault_version}"
CLUSTER_NAME="${cluster_name}"
RDS_ENDPOINT="${rds_endpoint}"
KMS_KEY_ID="${kms_key_id}"

# Install Vault
yum install -y unzip jq aws-cli
cd /tmp
curl -fsSL "https://releases.hashicorp.com/vault/${VAULT_VERSION}/vault_${VAULT_VERSION}_linux_amd64.zip" -o vault.zip
unzip vault.zip
mv vault /usr/local/bin/
vault --version

# Create vault user
useradd --system --home /opt/vault --shell /bin/false vault
mkdir -p /opt/vault/data /opt/vault/tls /etc/vault.d
chown -R vault:vault /opt/vault /etc/vault.d

# Generate self-signed TLS (replace with ACM in prod)
openssl req -x509 -newkey rsa:4096 -keyout /opt/vault/tls/vault.key -out /opt/vault/tls/vault.crt \
  -days 365 -nodes -subj "/CN=vault.${CLUSTER_NAME}.internal"

chown -R vault:vault /opt/vault/tls
chmod 600 /opt/vault/tls/vault.key

# Fetch DB password from Secrets Manager
DB_PASSWORD=$(aws secretsmanager get-secret-value --secret-id "navdhya/${CLUSTER_NAME}/vault/db-password" --query SecretString --output text)

# Write config
cat > /etc/vault.d/vault.hcl <<EOF
storage "postgresql" {
  connection_url = "postgresql://vaultadmin:${DB_PASSWORD}@${RDS_ENDPOINT}:5432/vault?sslmode=require"
  table          = "vault_kv_store"
  ha_enabled     = true
}

listener "tcp" {
  address     = "0.0.0.0:8200"
  tls_disable = false
  tls_cert_file = "/opt/vault/tls/vault.crt"
  tls_key_file  = "/opt/vault/tls/vault.key"
}

cluster_addr = "https://\$(curl -s http://169.254.169.254/latest/meta-data/local-ipv4):8201"
api_addr     = "https://\$(curl -s http://169.254.169.254/latest/meta-data/local-ipv4):8200"

ui = true
telemetry { prometheus_retention_time = "24h"; disable_hostname = true; }

seal "awskms" {
  region = "ap-south-1"
  kms_key_id = "${KMS_KEY_ID}"
}
EOF

chown vault:vault /etc/vault.d/vault.hcl

# Systemd service
cat > /etc/systemd/system/vault.service <<EOF
[Unit]
Description=Vault
Requires=network-online.target
After=network-online.target
ConditionFileNotEmpty=/etc/vault.d/vault.hcl

[Service]
User=vault
Group=vault
ExecStart=/usr/local/bin/vault server -config=/etc/vault.d/vault.hcl
ExecReload=/bin/kill --signal HUP \$MAINPID
KillMode=process
Restart=on-failure
RestartSec=5
TimeoutStartSec=30
LimitNOFILE=65536
LimitMEMLOCK=infinity

[Install]
WantedBy=multi-user.target
EOF

systemctl daemon-reload
systemctl enable vault
systemctl start vault

# Wait for Vault to be ready
sleep 10
until curl -sk https://localhost:8200/v1/sys/health | grep -q '"initialized":true'; do
  sleep 5
done

# Initialize if not already
if ! curl -sk https://localhost:8200/v1/sys/init | grep -q '"initialized":true'; then
  vault operator init -key-shares=5 -key-threshold=3 -format=json > /opt/vault/init.json
  # Store unseal keys + root token in Secrets Manager
  aws secretsmanager put-secret-value --secret-id "navdhya/${CLUSTER_NAME}/vault/init" --secret-string file:///opt/vault/init.json
  rm /opt/vault/init.json
fi

# Auto-unseal (handled by awskms seal)
```

---

## 5. IAM Policy (Vault EC2 Role)

```hcl
resource "aws_iam_policy" "vault" {
  name = "navdhya-vault-${var.environment}"
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [
      {
        Effect = "Allow"
        Action = [
          "kms:Decrypt", "kms:Encrypt", "kms:GenerateDataKey",
          "kms:DescribeKey", "kms:ReEncrypt*"
        ]
        Resource = aws_kms_key.vault.arn
      },
      {
        Effect = "Allow"
        Action = [
          "secretsmanager:GetSecretValue",
          "secretsmanager:PutSecretValue"
        ]
        Resource = "arn:aws:secretsmanager:ap-south-1:*:secret:navdhya/${CLUSTER_NAME}/vault/*"
      },
      {
        Effect = "Allow"
        Action = [
          "ec2:DescribeInstances", "ec2:DescribeTags"
        ]
        Resource = "*"
      }
    ]
  })
}
```

---

## 6. KMS Key (Auto-Unseal)

```hcl
resource "aws_kms_key" "vault" {
  description             = "Vault auto-unseal key for ${var.environment}"
  deletion_window_in_days = 30
  enable_key_rotation     = true
  policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Sid    = "AllowVaultRole"
      Effect = "Allow"
      Principal = { AWS = aws_iam_role.vault.arn }
      Action = ["kms:Decrypt", "kms:Encrypt", "kms:GenerateDataKey*", "kms:DescribeKey"]
      Resource = "*"
    }]
  })
}
```

---

## 7. Initialization & Unseal

```bash
# After all 3 nodes are healthy
export VAULT_ADDR=https://vault.navdhya.internal:8200
export VAULT_CACERT=/opt/vault/tls/vault.crt

# Initialize (run once)
vault operator init -key-shares=5 -key-threshold=3 -format=json > init.json

# Store in AWS Secrets Manager (one-time)
aws secretsmanager create-secret \
  --name "navdhya/prod/vault/init" \
  --secret-string file://init.json \
  --kms-key-id $KMS_KEY_ID

# Unseal (auto via awskms seal, but verify)
vault status  # Should show "Sealed: false"

# Enable audit
vault audit enable file file_path=/var/log/vault_audit.log

# Enable KV v2 engines per environment
vault secrets enable -path=secret/prod kv-v2
vault secrets enable -path=secret/staging kv-v2
vault secrets enable -path=secret/dev kv-v2
```

---

## 7. Policies & Auth

### 7.1 AppRole for ECS Tasks

```hcl
# Policy: ecs-tasks-prod
path "secret/data/prod/*" {
  capabilities = ["read", "list"]
}
path "secret/metadata/prod/*" {
  capabilities = ["list"]
}

# Auth role
vault write auth/approle/role/ecs-tasks-prod \
  token_policies="ecs-tasks-prod" \
  token_ttl=1h \
  token_max_ttl=4h \
  bind_secret_id=true \
  secret_id_ttl=24h
```

### 7.2 GitHub Actions (OIDC)

```hcl
# JWT Auth for GitHub Actions
vault write auth/jwt/config \
  oidc_discovery_url="https://token.actions.githubusercontent.com" \
  bound_issuer="https://token.actions.githubusercontent.com"

vault write auth/jwt/role/github-actions-prod \
  role_type="jwt" \
  user_claim="repository" \
  bound_claims='{"repository": "navdhya/platform-navdhya"}' \
  token_policies="ecs-tasks-prod" \
  token_ttl=20m
```

---

## 8. Secret Structure (KV v2)

```
secret/
├── prod/
│   ├── redis/host
│   ├── redis/auth_token
│   ├── sendgrid/transactional/api_key
│   ├── sendgrid/marketing/api_key
│   ├── sendgrid/from_address
│   ├── sendgrid/webhook_secret
│   ├── db/password
│   ├── app/key (Laravel APP_KEY)
│   ├── jwt/secret
│   └── ...
├── staging/
└── dev/
```

---

## 9. Monitoring

| Metric | Source | Alert |
|---|---|---|
| `vault.core.leader` | Prometheus | Alert if no leader > 30s |
| `vault.core.sealed` | Prometheus | Alert if sealed > 0 |
| `vault.audit.log_request` | CloudWatch | Audit trail |
| `vault.token.creation` | Prometheus | Spike alert |

---

## 10. Backup / DR

- RDS automated snapshots (30 days)
- Vault initialization keys in Secrets Manager (cross-region replication)
- Disaster recovery: restore RDS snapshot → launch new ASG → auto-unseal

---

## 11. Rollback

```bash
# Scale ASG to 0
aws autoscaling set-desired-capacity --auto-scaling-group-name navdhya-vault-prod --desired-capacity 0

# Restore RDS from snapshot
aws rds restore-db-instance-from-db-snapshot ...

# Update launch template with previous version
aws ec2 create-launch-template-version --launch-template-name navdhya-vault-prod --source-version 1

# Scale back up
aws autoscaling set-desired-capacity --auto-scaling-group-name navdhya-vault-prod --desired-capacity 3
```

---

## 12. Cost Estimate (Monthly, `ap-south-1`)

| Component | Spec | Est. Cost |
|---|---|---|
| EC2 (3 × t3.medium) | 2 vCPU, 4 GB | ~$90 |
| RDS PostgreSQL (db.r6g.large) | 2 vCPU, 16 GB, 100 GB | ~$280 |
| NLB | 1 LCU | ~$18 |
| KMS Key | 1 key + requests | ~$5 |
| Secrets Manager | 10 secrets + API calls | ~$5 |
| **Total (prod)** | | **~$398** |
| **Staging** (t3.micro, db.t3.micro) | | **~$25** |
| **Dev** (t3.micro, db.t3.micro) | | **~$25** |