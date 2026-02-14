# Docker-First Barber Booking System

Production-oriented barber booking platform built with Next.js App Router, Prisma, PostgreSQL, and NextAuth.

## Architecture

- Frontend: Next.js + TypeScript + Tailwind + shadcn/ui components
- Backend: Route handlers + service layer + repository pattern
- Database: PostgreSQL with Prisma ORM + indexed time-based schema
- Auth: NextAuth credentials provider + role-based access (BARBER/ADMIN)
- Security: Zod server-side validation, CSRF checks, rate limiting, sanitization, secure headers, structured logs
- Concurrency: booking transaction locks availability rows (`FOR UPDATE`) so overbooking cannot occur

## Folder Structure

- `app/` routes and API handlers
- `components/` UI, booking flow, dashboard
- `lib/` shared utilities and infra clients
- `server/` services, repositories, validators, security, logging
- `prisma/` schema + SQL migrations

## Quick Start (Docker)

1. Create local environment:

```bash
cp .env.example .env
```

2. Set strong values in `.env`:
- `AUTH_SECRET` (>= 32 chars)
- `BARBER_BOOTSTRAP_EMAIL`
- `BARBER_BOOTSTRAP_PASSWORD` (>= 12 chars)

3. Start stack:

```bash
docker compose up -d --build
```

4. Open app:
- Booking UI: `http://localhost:3000`
- Barber login: `http://localhost:3000/login`
- Optional Adminer: `docker compose --profile tools up -d adminer` then `http://localhost:8080`

## Migration Strategy

- Committed SQL migration in `prisma/migrations/20260214130000_init/migration.sql`
- `migrate` service runs automatically on startup: `prisma migrate deploy`
- For new schema changes:

```bash
npx prisma migrate dev --name <change-name>
```

Then commit generated migration files and redeploy. `docker compose up` will apply pending migrations automatically.

## Security Notes

- No booking write endpoint trusts client-side validation.
- CSRF token required on all mutating booking/availability endpoints.
- In-memory rate limiter protects booking creation endpoint.
- Input sanitization is enforced server-side for user-provided strings.
- Middleware adds CSP and hardening headers.

## Operational Notes

- Database is internal-only in Docker network (not published to host).
- Booking data persists via named volume `postgres_data`.
- App image uses standalone production build and non-root runtime user.
