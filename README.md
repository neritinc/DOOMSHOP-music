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



# RADME.md

> A szoftver működésének műszaki feltételei (DOOMSHOP-music)

## Tartalom
- [Projekt röviden](#projekt-röviden)
- [Szükséges szoftverek](#szükséges-szoftverek)
- [Repo szerkezet](#repo-szerkezet)
- [Telepítés és futtatás](#telepítés-és-futtatás)
- [Tesztek futtatása](#tesztek-futtatása)
- [Kötelező beadandó fájlok](#kötelező-beadandó-fájlok)

## Projekt röviden
A **DOOMSHOP-music** egy Laravel + Vue alapú zenei webshop alkalmazás.

## Szükséges szoftverek
| Eszköz | Verzió |
|---|---|
| Git | aktuális |
| PHP | 8.2+ |
| Composer | 2+ |
| Node.js | 20+ |
| npm | 10+ |
| MySQL | 8+ |
| FFmpeg + FFprobe | preview generáláshoz |

## Repo szerkezet
```text
DOOMSHOP-music/
├── server/
├── client/
└── DokumnetacioKepek/
```

## Telepítés és futtatás
### 1) Forráskód letöltése
```bash
git clone <repo-url>
cd DOOMSHOP-music
```

### 2) Backend (`server/`)
```bash
cd server
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Elérés:
- Backend: `http://127.0.0.1:8000`
- API: `http://127.0.0.1:8000/api`

### 3) Frontend (`client/`)
```bash
cd client
npm install
npm run dev
```

Elérés:
- Frontend: `http://localhost:5173`

## Tesztek futtatása
### Backend
```bash
cd server
php artisan test
```

### Frontend unit
```bash
cd client
npm run test:unit
```

### Frontend E2E
```bash
cd client
npm run test:e2e
```

## Kötelező beadandó fájlok
- [x] `RADME.md`
- [x] `Dokumentáció.md`
- [x] `Tesztek.md`
- [x] `Diagram.png`
- [x] `AdatbazisBackup.sql`

## Vizsgakövetelmény megfelelés (rövid)
- [x] Életszerű probléma megoldása
- [x] CRUD műveletek
- [x] RESTful szerver + kliens
- [x] Autentikáció és jogosultságkezelés

