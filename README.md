# DOOMSHOP-music

Vizsgaremek projekt: online zenei webshop (DOOMSHOP Records) Laravel backenddel és Vue frontenddel.

## Projektcél
A rendszer egy valós problémára ad megoldást: zenei tartalmak katalogizálása, adminisztrációja és felhasználói vásárlási folyamata egyetlen platformon.

Főbb képességek:
- autentikáció (regisztráció, login, logout)
- szerepkör alapú jogosultságkezelés (`admin`, `guest/customer`)
- teljes CRUD a fő entitásokra (tracks, artists, genres, albums, users, carts)
- kosár és checkout folyamat
- admin felület és felhasználói felület
- REST API + külön kliens alkalmazás

## Technológia
- Backend: Laravel 12, PHP 8.2+, Sanctum, MySQL
- Frontend: Vue 3, Vite, Pinia, Vue Router, Axios, Bootstrap 5
- Tesztek: PHPUnit (backend), Vitest + Cypress (frontend)

## Repo felépítése
- `server/` -> Laravel API
- `client/` -> Vue kliens
- `DokumnetacioKepek/` -> képernyőképek (ha használjátok dokumentálásra)

## Gyors indítás (fejlesztői környezet)

### 1) Forráskód letöltése
```bash
git clone <repo-url>
cd DOOMSHOP-music
```

### 2) Backend beállítás (`server/`)
```bash
cd server
composer install
copy .env.example .env
php artisan key:generate
```

MySQL adatbázis létrehozása után:
```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

### 3) Frontend beállítás (`client/`)
Új terminálban:
```bash
cd client
npm install
npm run dev
```

### 4) Alkalmazás elérése
- Frontend: `http://localhost:5173`
- Backend API: `http://127.0.0.1:8000/api`

## Kötelező vizsgadokumentumok
A gyökérben szerepeljen:
- `README.md` vagy `RADME.md` (műszaki futtatási leírás)
- `Dokumentáció.md`
- `Tesztek.md`
- `Diagram.png`
- `AdatbazisBackup.sql`

Ebben a repóban a részletes szakmai leírások:
- `Dokumentáció.md`
- `Tesztek.md`
