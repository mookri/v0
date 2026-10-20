# REDIS-001: Managed Redis (AWS ElastiCache)

**Target:** Redis 7.x cluster for cache, sessions, queues, rate limiting
**Provider:** AWS ElastiCache (Redis 7.1+)
**Region:** `ap-south-1` (Mumbai) — primary; `ap-southeast-1` (Singapore) — DR

---

## 1. Architecture

```
┌─────────────────┐     ┌──────────────────┐
│  Laravel App    │────▶│  ElastiCache     │
│  (ECS Fargate)  │     │  (Cluster Mode)  │
└─────────────────┘     └──────────────────┘
                               │
                    ┌──────────┴──────────┐
                    ▼                     ▼
             ┌─────────────┐       ┌─────────────┐
             │ Primary     │       │ Replica (2) │
             │ (read/write)│       │ (read-only) │
             └─────────────┘       └─────────────┘
```

---

## 2. Terraform Module (`infra/redis/main.tf`)

```hcl
module "redis" {
  source  = "cloudposse/elasticache-redis/aws"
  version = "~> 1.0"

  name                = "navdhya-${var.environment}"
  engine_version      = "7.1"
  node_type           = var.environment == "prod" ? "cache.r6g.xlarge" : "cache.t3.micro"
  number_cache_nodes  = var.environment == "prod" ? 3 : 1
  parameter_group_name = "navdhya-redis7"
  port                = 6379
  automatic_failover_enabled = var.environment == "prod"

  # Network
  vpc_id              = var.vpc_id
  subnet_ids          = var.private_subnet_ids
  security_group_ids  = [aws_security_group.redis.id]

  # Encryption
  at_rest_encryption_enabled = true
  transit_encryption_enabled = true
  auth_token            = var.redis_auth_token  # from Vault

  # Maintenance
  preferred_maintenance_window = "sun:03:00-sun:05:00"
  snapshot_retention_limit     = 7
  snapshot_window              = "02:00-04:00"

  # Logging
  log_delivery_configuration {
    destination      = aws_cloudwatch_log_group.redis.id
    log_format       = "json"
    log_type         = "slow-log"
  }

  tags = local.common_tags
}
```

---

## 3. Security Group

```hcl
resource "aws_security_group" "redis" {
  name        = "navdhya-${var.environment}-redis"
  vpc_id      = var.vpc_id
  description = "Allow Redis from ECS tasks only"

  ingress {
    from_port   = 6379
    to_port     = 6379
    protocol    = "tcp"
    security_groups = [aws_security_group.ecs_tasks.id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}
```

---

## 4. Redis Configuration (Parameter Group)

| Parameter | Value | Reason |
|---|---|---|
| `maxmemory-policy` | `allkeys-lru` | Evict LRU when full |
| `timeout` | `300` | Close idle connections |
| `tcp-keepalive` | `60` | Detect dead connections |
| `slowlog-log-slower-than` | `10000` | Log slow commands (>10ms) |
| `slowlog-max-len` | `512` | Keep last 512 slow queries |
| `notify-keyspace-events` | `Ex` | Expired/evicted key events |

---

## 5. Laravel Config (`config/cache.php`, `config/session.php`, `config/queue.php`)

```php
// config/cache.php
'redis' => [
    'client' => 'phpredis',
    'default' => [
        'host' => env('REDIS_HOST'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => 0,
        'read_write_timeout' => 60,
    ],
    'session' => ['database' => 1],
    'queue' => ['database' => 2],
    'cache' => ['database' => 3],
    'rate_limit' => ['database' => 4],
],

// config/session.php
'driver' => 'redis',
'connection' => 'session',

// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'queue',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
],
```

---

## 6. Environment Variables (Injected from Vault)

| Variable | Source | Rotation |
|---|---|---|
| `REDIS_HOST` | Vault: `redis/prod/host` | On cluster rebuild |
| `REDIS_PASSWORD` | Vault: `redis/prod/auth_token` | 90 days |
| `REDIS_PORT` | Static: `6379` | N/A |

---

## 7. Monitoring & Alerts (CloudWatch)

| Metric | Threshold | Action |
|---|---|---|
| `CPUUtilization` | > 80% for 5m | Scale up node type |
| `DatabaseMemoryUsagePercentage` | > 85% | Alert + increase `maxmemory` |
| `CurrConnections` | > 90% of max | Alert |
| `ReplicationLag` | > 30s | Alert (prod only) |
| `Evictions` | > 100/min | Alert + review cache strategy |

---

## 8. Backup / DR

- Automated snapshots: daily, 7-day retention (prod)
- Cross-region replication: `ap-southeast-1` (Singapore)
- Point-in-time recovery: enabled (prod)

---

## 9. Rollback

```bash
# Scale down to previous node type
terraform apply -var="node_type=cache.t3.medium"

# Restore from snapshot
aws elasticache create-replication-group \
  --replication-group-id navdhya-prod-restored \
  --snapshot-name navdhya-prod-snapshot-2026-10-01
```

---

## 10. Cost Estimate (Monthly, `ap-south-1`)

| Environment | Node Type | Nodes | Est. Cost |
|---|---|---|---|
| prod | cache.r6g.xlarge | 3 (1 primary + 2 replica) | ~$420 |
| staging | cache.t3.medium | 1 | ~$35 |
| dev | cache.t3.micro | 1 | ~$12 |