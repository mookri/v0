# syntax=docker/dockerfile:1.7
FROM node:22-alpine AS base
RUN corepack enable pnpm
WORKDIR /app
ENV NEXT_TELEMETRY_DISABLED=1

FROM base AS deps
COPY pnpm-lock.yaml pnpm-workspace.yaml package.json ./
COPY packages ./packages
COPY apps/partner/package.json ./apps/partner/package.json
RUN pnpm install --frozen-lockfile --filter @navdhya/partner-app...

FROM deps AS builder
COPY apps/partner ./apps/partner
ENV NODE_ENV=production
RUN pnpm --filter @navdhya/partner-app build

FROM node:22-alpine AS runner
WORKDIR /app
ENV NODE_ENV=production
ENV NEXT_TELEMETRY_DISABLED=1
ENV PORT=3002
ENV HOSTNAME=0.0.0.0

RUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nextjs
COPY --from=builder --chown=nextjs:nodejs /app/apps/partner/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/apps/partner/.next/static ./apps/partner/.next/static
COPY --from=builder --chown=nextjs:nodejs /app/apps/partner/public ./apps/partner/public

USER nextjs
EXPOSE 3002
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
  CMD wget -qO- http://127.0.0.1:3002/api/healthz || exit 1
CMD ["node", "apps/partner/server.js"]

