# syntax=docker/dockerfile:1.7
FROM node:22-alpine AS base
RUN corepack enable pnpm
WORKDIR /app
ENV NEXT_TELEMETRY_DISABLED=1

FROM base AS deps
COPY pnpm-lock.yaml pnpm-workspace.yaml package.json ./
COPY packages ./packages
COPY apps/customer/package.json ./apps/customer/package.json
RUN pnpm install --frozen-lockfile --filter @navdhya/customer...

FROM deps AS builder
COPY apps/customer ./apps/customer
ENV NODE_ENV=production
RUN pnpm --filter @navdhya/customer build

FROM node:22-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ENV PORT=3001
ENV HOSTNAME=0.0.0.0

RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nextjs
COPY --from=builder --chown=nextjs:nodejs /app/apps/customer/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/apps/customer/.next/static ./apps/customer/.next/static

USER nextjs
EXPOSE 3001
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
  CMD wget -qO- http://127.0.0.1:3001/api/healthz || exit 1
CMD ["node", "apps/customer/server.js"]

