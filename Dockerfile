FROM node:20-alpine AS base
WORKDIR /app
RUN apk add --no-cache libc6-compat openssl
ENV NEXT_TELEMETRY_DISABLED=1

FROM base AS deps
COPY package.json ./
COPY prisma ./prisma
RUN npm install --no-audit --no-fund

FROM base AS builder
ENV NODE_ENV=production
ENV DATABASE_URL=postgresql://postgres:postgres@db:5432/barber_booking?schema=public
ENV AUTH_SECRET=build-time-secret-build-time-secret-1234
ENV NEXTAUTH_URL=http://localhost:3000
COPY --from=deps /app/node_modules ./node_modules
COPY . .
RUN npm run build

FROM base AS migrator
ENV NODE_ENV=production
COPY --from=deps /app/node_modules ./node_modules
COPY package.json ./
COPY prisma ./prisma
COPY scripts ./scripts
CMD ["sh", "-c", "node scripts/validate-env.mjs && npx prisma migrate deploy && node scripts/bootstrap-barber.mjs"]

FROM base AS runner
ENV NODE_ENV=production
ENV PORT=3000
ENV HOSTNAME=0.0.0.0
RUN addgroup -S nodejs && adduser -S nextjs -G nodejs
COPY --from=builder --chown=nextjs:nodejs /app/public ./public
COPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./
COPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static
COPY --chown=nextjs:nodejs scripts/validate-env.mjs ./scripts/validate-env.mjs
USER nextjs
EXPOSE 3000
CMD ["sh", "-c", "node scripts/validate-env.mjs && node server.js"]
