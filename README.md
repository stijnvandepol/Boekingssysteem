# Rens Boekingssysteem

Productieklare Laravel booking app die volledig in Docker draait.

## Snelle start (lokaal)

1. Kopieer `.env.example` naar `.env` en pas wachtwoorden aan.
2. Build en start containers:
   - `docker compose up -d --build`
3. App key + migraties + seed gebeuren automatisch bij startup (via `AUTO_MIGRATE`/`AUTO_SEED`).
   - Handmatig kan ook met:
     - `docker compose exec app php artisan key:generate`
     - `docker compose exec app php artisan migrate --force`
     - `docker compose exec app php artisan db:seed --force`

App draait op `http://localhost:8080`.

## Admin login

- E‑mail: `admin@example.com`
- Wachtwoord: `ChangeMe123!`

## Productie

- Zet `APP_URL` naar je publieke URL.
- Zet `SESSION_SECURE_COOKIE=true`.
- Draai `docker compose up -d --build`.
- Cloudflare Tunnel wijst naar `http://<host>:8080`.

## Healthcheck

- `GET /health` retourneert `{ "status": "ok" }`.
