# Dokumentáció

> Projekt: **DOOMSHOP-music**

## Tartalom
- [A szoftver célja](#a-szoftver-célja)
- [Használat röviden](#használat-röviden)
- [Vizsgakövetelmény megfelelés](#vizsgakövetelmény-megfelelés)
- [Technikai leírás](#technikai-leírás)
- [Ütemezés](#ütemezés)
- [Vizsgabemutató tartalom](#vizsgabemutató-tartalom)
- [Forráslista](#forráslista)

## A szoftver célja
A DOOMSHOP-music egy online zenei webshop, ahol:
- a felhasználók zenéket böngésznek, kosárba tesznek, vásárolnak és letöltenek,
- az adminisztrátorok teljes katalóguskezelést végeznek.

Megvalósított fő funkciók:
- autentikáció: regisztráció, login, logout,
- szerepkör alapú jogosultságkezelés,
- CRUD több entitásra,
- REST API + külön frontend kliens.

## Használat röviden
1. Regisztráció vagy bejelentkezés: `/registration`, `/login`
2. Katalógus böngészés: `/tracks`, `/artists`, `/genres`, `/albums`
3. Track adatlap + preview: `/tracks/:id`
4. Kosárkezelés: `/my-cart`
5. Checkout + letöltés

További oldalak:
- ajánlók: `/recommendations`
- live show és mixek: `/liveshows-mixes`
- admin kosarak: `/admin-carts`

Képernyőképek helye:
- `DokumnetacioKepek/`

## Vizsgakövetelmény megfelelés
- [x] Életszerű probléma: webshop folyamat (katalógus + kosár + checkout)
- [x] CRUD: `users`, `tracks`, `artists`, `genres`, `albums`, `carts`, `cart_items`
- [x] RESTful architektúra: Laravel API + Axios alapú kliens
- [x] Asztali és mobil használat: reszponzív Vue + Bootstrap
- [x] Kötelező dokumentációs elemek megléte

## Technikai leírás

### Adatbázis
**Technológia:** MySQL, Laravel migrációk és seederek

**Fő táblák:**
- `users`
- `tracks`
- `artists`
- `genres`
- `albums`
- `track_artists`
- `track_genres`
- `carts`
- `cart_items`
- `liveshow_links`
- `recommendation_links`
- `personal_access_tokens`

**Mellékletek:**
- `Diagram.png`
- `AdatbazisBackup.sql`

### Backend
**Technológia:** Laravel 12, PHP 8.2+, Sanctum, FFmpeg/FFprobe

**Fő funkciók:**
- feltöltés + metadata elemzés: `POST /api/tracks/analyze-upload`
- preview stream: `GET /api/tracks/{id}/preview`
- preview újragenerálás: `POST /api/tracks/{id}/regenerate-preview`
- szerepkörfüggő hozzáférés (`ability:admin`, stb.)

**Szerkezet:**
- migrációk: `server/database/migrations/`
- seederek: `server/database/seeders/`
- forrás adatok: `server/database/csv/`
- route: `server/routes/api.php`
- kontrollerek: `server/app/Http/Controllers/`
- modellek: `server/app/Models/`
- validáció: `server/app/Http/Requests/`

**Endpoint példák:**
- nyilvános:
  - `POST /api/users`
  - `POST /api/users/login`
  - `GET /api/tracks`
  - `GET /api/genres`
- védett:
  - `POST /api/tracks` (`auth:sanctum`, `ability:admin`)
  - `GET /api/my-carts` (`auth:sanctum`, `ability:carts:self:get`)
  - `POST /api/my-carts/{id}/checkout` (`auth:sanctum`, `ability:checkout:self:post`)

**Autentikáció:**
- regisztráció: `POST /api/users`
- login: `POST /api/users/login`
- logout: `POST /api/users/logout`
- token: Sanctum Bearer token
- szerepkörök: admin, customer/vendég

### Frontend
**Technológia:** Vue 3, Vite, Pinia, Vue Router, Axios, Bootstrap

**Belépési pontok:**
- `client/src/main.js`
- `client/src/App.vue`

**Fájlstruktúra:**
- API service-ek: `client/src/api/`
- store-ok: `client/src/stores/`
- komponensek: `client/src/components/`
- nézetek: `client/src/views/`
- router: `client/src/router/index.js`

**Jogosultságkezelés a kliensen:**
- route szint: `meta.roles`
- route guard: bejelentkezés/szerepkör ellenőrzés
- menü szint: admin elemek feltételes megjelenítése

**Oldalak (példák):**
- `TracksView.vue`, `TrackDetailView.vue`
- `ArtistsView.vue`, `GenresView.vue`, `AlbumsView.vue`
- `MyCartView.vue`, `AdminCartsView.vue`
- `MusicRecommendations.vue`, `LiveshowsMixesView.vue`

## Ütemezés
- **2026 január:** backend alapok, adatbázis, auth, API
- **2026 február-március:** frontend nézetek, store-ok, service-ek, CRUD
- **2026 április:** dokumentáció, tesztek rendezése, bemutató anyag

## Vizsgabemutató tartalom
- szoftver célja és probléma
- adatbázis és architektúra
- élő működés
- backend + frontend kódrészletek
- csapatmunka és munkamegosztás
- 3-5 perces angol összefoglaló + angol Q&A

## Forráslista
- Laravel: https://laravel.com/docs
- Vue: https://vuejs.org/
- Vue Router: https://router.vuejs.org/
- Pinia: https://pinia.vuejs.org/
- Axios: https://axios-http.com/
- Bootstrap: https://getbootstrap.com/
