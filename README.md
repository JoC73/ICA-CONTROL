# ICA-CONTROL

Monorepo inicial basado en el PDF de arquitectura:

- `backend`: Laravel API + Sanctum, lista para PostgreSQL/Render.
- `mobile`: Expo + React Native, interfaz movil para dashboard, movimientos, reportes y registro rapido.

## Backend

```bash
cd backend
composer install
cp .env.render.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Usuario seed:

- Email: `admin@appfinanzas.test`
- Password: `ControlSeguro2026`

## Mobile

```bash
cd mobile
npm install
npm run start
```

Configura la API en `mobile/src/api/client.ts` si tu backend usa otra URL.

Para iOS:

```bash
cd mobile
npm run ios
```

Tambien puedes abrir el proyecto con Expo Go en iPhone desde `npm run start`.

Para construir APK con EAS:

```bash
cd mobile
npx eas-cli build -p android --profile preview
```

La app esta configurada con:

- Nombre visible: `ICA-CONTROL`
- Android package: `com.icacontrol.mobile`
- iOS bundle id: `com.icacontrol.mobile`
- Icono y splash en `mobile/assets`

Para revisar la version web exportada:

```bash
cd mobile
npx expo export --platform web --output-dir dist-web
php -S 127.0.0.1:8090 -t dist-web
```

## Mejoras incluidas

- Roles base: superadmin, admin y user.
- Tokens moviles con Sanctum.
- Catalogos de categorias, cuentas y metodos de pago.
- Movimientos unificados de ingresos/egresos para reportes mas eficientes.
- Tickets generados al registrar movimientos.
- Auditoria basica de creacion/edicion.
- Filtros por tipo, categoria, usuario, cuenta, rango y busqueda.
- Preparado para Render/PostgreSQL.

## Mejoras IU/UX aplicadas

- Registro rapido desde boton superior y acciones principales de ingreso/egreso.
- Modal inferior optimizado para movil con montos rapidos, categorias y guardado en menos toques.
- Navegacion inferior fija para flujo mas rapido en Android, iOS y web.
- Lista de movimientos con `FlatList`, render memoizado y filtros instantaneos.
- Dashboard mas escaneable con saldo principal, alertas y barras de gasto.
- Configuracion Expo preparada para iOS con `bundleIdentifier` y soporte tablet.

## Railway

El backend queda preparado para Railway en `backend/RAILWAY.md`.

Railway recomienda desplegar Laravel desde GitHub o CLI, agregar variables de entorno, usar PostgreSQL con `DB_URL=${{Postgres.DATABASE_URL}}`, correr migraciones en Pre-Deploy y enviar logs a `stderr`. En este proyecto:

- Variables base: `backend/.env.railway.example`
- Pre-Deploy: `chmod +x ./railway/init-app.sh && sh ./railway/init-app.sh`
- Worker opcional: `chmod +x ./railway/run-worker.sh && sh ./railway/run-worker.sh`
- Cron opcional: `chmod +x ./railway/run-cron.sh && sh ./railway/run-cron.sh`
