# Runbook

## Migraties

- `docker compose exec app php artisan migrate --force`
- Rollback: `docker compose exec app php artisan migrate:rollback --force`

## Backups (MySQL)

- Backup: `docker compose exec db mysqldump -u root -p$MYSQL_ROOT_PASSWORD rens > backup.sql`
- Restore: `docker compose exec -T db mysql -u root -p$MYSQL_ROOT_PASSWORD rens < backup.sql`

## Queues

- Worker draait in container `queue`.
- Failed jobs bekijken: `docker compose exec app php artisan queue:failed`
- Failed job opnieuw: `docker compose exec app php artisan queue:retry <id>`

## Scheduler

- `schedule:work` draait in container `scheduler`.

## Logs

- `docker compose logs -f app`
- `docker compose logs -f web`
