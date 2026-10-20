# Local Platform (§16)

One command, two services, zero required. Memory adapters stay the default;
each production binding lands behind its port separately.

## Commands

```sh
docker compose up -d postgres redis   # infra:up (alias pending pnpm wiring)
docker compose down                   # infra:down (keeps volumes)
docker compose down -v                # infra:reset (drops pgdata/redisdata)
docker compose ps                     # infra status
```

Connection (dev only, never production credentials):

- Postgres: `postgres://navdhya:navdhya@localhost:5432/navdhya`
- Redis: `redis://localhost:6379`

## Binding order (one port per commit, each reversible)

1. Outbox store → Postgres (`OutboxStore` port exists; memory impl today).
2. Event bus → Redis streams (`EventBus` port; replay via `XREVRANGE`).
3. Session store → Redis (`SessionStore` port; login/logout untouched).
4. Search index → Postgres FTS or dedicated engine (`SearchEngine` port).

Each binding ships with: adapter in `infrastructure/`, env-selected
composition, contract tests unchanged (ports don't move), and a staging
note in `staging-deploy.md`.

## Rules

- No production credentials locally, ever (dev-only values live in this
  file, which is not secret).
- Seed data comes later with the first binding (`db:seed` per binding, not
  a global dump).
- If compose isn't running, everything still works on memory adapters —
  the platform degrades to local, never breaks.
