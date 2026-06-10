# Deploy ICA-CONTROL API en Railway

Railway detecta Laravel automaticamente y lo ejecuta con PHP-FPM y Caddy cuando el servicio apunta al root del backend.

## Servicio principal

1. Crea un proyecto en Railway.
2. Agrega una base PostgreSQL.
3. Crea un servicio desde GitHub apuntando a `backend`.
4. En Variables, usa como base `.env.railway.example`.
5. Agrega `APP_KEY` generado con:

```bash
php artisan key:generate --show
```

6. En Pre-Deploy Command coloca:

```bash
chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh
```

7. Genera dominio publico en Networking.

## Worker opcional

Start Command:

```bash
chmod +x ./railway/run-worker.sh && sh ./railway/run-worker.sh
```

## Cron opcional

Start Command:

```bash
chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh
```

## Variables clave

- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_CHANNEL=stderr`
- `DB_CONNECTION=pgsql`
- `DB_URL=${{Postgres.DATABASE_URL}}`
- `QUEUE_CONNECTION=database`
