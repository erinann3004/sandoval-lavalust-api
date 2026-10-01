# Laboratory Exercise 6 Setup

This repository includes an authenticated React product-management client and a LavaLust JSON API. The API uses JWT access/refresh tokens and the existing `accounts` table (`firstname`, `lastname`, `username`, `email`, `password`, `role`, and `is_active`); the original PHP-rendered session login remains available separately. The `users` migration remains available to match the migration exercise, but API registration uses `accounts`.

## Local setup

1. Copy the root `.env.example` to `.env`. Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_SSL_CA`, `JWT_SECRET`, and `REFRESH_TOKEN_KEY`. Generate separate random JWT keys with at least 32 characters. Never commit `.env`.
2. Configure the Aiven MySQL CA certificate path in `DB_SSL_CA` when certificate verification is enabled. The database connection accepts both `DB_USER` and the legacy `DB_USERNAME` variable.
3. Run `php lava migration status` and then `php lava migration run`. These migrations create `migrations`, the original `accounts` and `products` tables, plus `users` and `refresh_tokens` for the API.
4. Start the backend with `php lava serve 3000`.
5. In another terminal, run `cd frontend`, copy `.env.example` to `.env`, set `VITE_API_BASE_URL=http://localhost:3000/api`, then run `npm install` and `npm run dev`. Open the URL Vite prints (normally `http://localhost:5173`).
6. Register from the React app. API accounts are stored in `accounts`; the `users` migration is included separately because the migration exercise lists that table as an expected output.

## API endpoints

- `POST /api/register` with `{ "firstname", "lastname", "username", "email", "password" }`
- `POST /api/login` with `{ "email", "password" }`
- `POST /api/refresh` with `{ "refresh_token" }`
- `POST /api/logout` with `{ "refresh_token" }`
- Authenticated `GET /api/products`
- Authenticated `POST /api/products` with `product_name`, `description`, `price`, `quantity`
- Authenticated `PUT` or `PATCH /api/products/{id}`
- Authenticated `DELETE /api/products/{id}`

Product endpoints require `Authorization: Bearer <access_token>`. API responses are emitted by LavaLust's `Api` library. Set `API_ALLOWED_ORIGINS` to the exact frontend origin in production; comma-separated origins are supported. Do not use `*` when sending credentialed browser requests.

## Migration exercise commands

The CLI command is registered by `app/commands/migration.php`:

```sh
php lava migration run
php lava migration status
php lava migration create-migration create_products_table
php lava migration rollback
php lava migration rollback-all
php lava migration refresh
```

The web migration routes described in the activity are also present and protected by the existing session `auth` middleware: `/create-migration/{migration_class}`, `/migrate`, `/rollback`, `/rollback-all`, `/refresh`, and `/status`. Prefer the CLI for routine work. Rollback-all and refresh can destroy data and should only be used on a disposable development database.

## Render deployment

Deploy the LavaLust API as a Render Docker web service using the repository's `Dockerfile`. Configure `APP_ENV=production`, `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, `DB_NAME`, `DB_SSL_CA`, `JWT_SECRET`, `REFRESH_TOKEN_KEY`, and `API_ALLOWED_ORIGINS` in Render's environment settings. Keep secrets out of GitHub. Run `php lava migration run` using the same production environment before testing API endpoints.

Deploy `frontend/` as a separate Render static site. Set its build command to `npm install && npm run build`, publish directory to `dist`, and `VITE_API_BASE_URL` to `https://YOUR-API.onrender.com/api`. The React app sends HTTP requests only to LavaLust; it never connects to MySQL.

## Submission checklist

- Push the API repository and frontend repository to GitHub (they may be separate repositories for the assignment).
- Record the Render API URL and deployed frontend URL.
- Capture login, product list, add/edit, delete, and a working end-to-end demonstration.