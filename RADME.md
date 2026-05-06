# A szoftver működésének műszaki feltételei

## A fejlesztési környezethez szükséges szoftverek
- Operációs rendszer: Windows 10/11, Linux vagy macOS
- PHP: 8.2+
- Composer: 2.x
- Node.js: 20.19+ (a `client/package.json` alapján)
- NPM: Node.js-hez tartozó csomagkezelő
- MySQL/MariaDB: adatbázis szerver
- Git: forráskód kezelés
- Ajánlott eszközök: VS Code, Postman vagy REST Client

## Forráskód letöltése
```bash
git clone <repo-url>
cd DOOMSHOP-music
```

## A program teszt környezetének telepítése, futtatása

### 1. Backend (Laravel) telepítés
```bash
cd server
composer install
copy .env.example .env
php artisan key:generate
```

`.env` fájlban állítsd be legalább ezeket:
- `DB_CONNECTION=mysql`
- `DB_HOST=127.0.0.1`
- `DB_PORT=3306`
- `DB_DATABASE=doomshop`
- `DB_USERNAME=...`
- `DB_PASSWORD=...`

Migráció + seed:
```bash
php artisan migrate --seed
php artisan storage:link
```

Backend indítás:
```bash
php artisan serve
```
Alap API: `http://127.0.0.1:8000/api`

### 2. Frontend (Vue + Vite) telepítés
Új terminálban:
```bash
cd client
npm install
npm run dev
```
Frontend: `http://localhost:5173`

### 3. Gyors ellenőrzés
- Backend health endpoint: `GET /api/x`
- Bejelentkezés minta: `POST /api/users/login`
- Kézi API tesztek: `server/request.rest`
