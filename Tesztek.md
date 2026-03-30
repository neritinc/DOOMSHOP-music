# Tesztek

> Projekt: **DOOMSHOP-music**

## Tartalom
- [Kézi tesztelés (`request.rest`)](#kézi-tesztelés-requestrest)
- [Backend tesztek](#backend-tesztek)
- [Frontend tesztek](#frontend-tesztek)
- [Bemutató tesztforgatókönyv](#bemutató-tesztforgatókönyv)
- [Teszteredmények dokumentálása](#teszteredmények-dokumentálása)

## Kézi tesztelés (`request.rest`)
Fájl:
- `server/request.rest`

A kézi tesztcsomag ellenőrzi:
- health endpoint: `GET /api/x`
- admin + customer login
- tracks CRUD jogosultság
- genres/artists jogosultság
- `usersme` profilműveletek
- `carts` vs `my-carts` hozzáférés
- `my-cart-items` műveletek
- logout

### Bejelentkezési minta
- `POST /api/users/login`
- token változók: `@admin_token`, `@customer_token`

### CRUD minta
- `POST /api/tracks` (admin: `201`, customer: `403`)
- `GET /api/tracks` (admin és customer: `200`)

## Backend tesztek
**Keretrendszer:** PHPUnit

Futtatás:
```bash
cd server
php artisan test
```

Példafájlok:
- `server/tests/Feature/UserTest.php`
- `server/tests/Feature/UserUpdateTest.php`
- `server/tests/Unit/UserTest.php`

Lefedett területek:
- login/logout
- user létrehozás
- policy/jogosultság ellenőrzés
- validációs hibák (`422`)

## Frontend tesztek
**Teszttípusok:**
- Vitest (unit/component)
- Cypress (E2E)

Futtatás:
```bash
cd client
npm run test:unit
npm run test:e2e
```

Példa unit teszt:
- `client/src/__tests__/App.spec.js`

E2E alapfájlok:
- `client/cypress.config.js`
- `client/cypress/e2e/`

## Bemutató tesztforgatókönyv
1. Admin login
2. Track létrehozás adminnal
3. Ugyanez customer tokennel (`403`)
4. Customer saját kosár létrehozás + tétel hozzáadás
5. Customer `GET /api/carts` hívás (`403`)
6. Checkout + logout

## Teszteredmények dokumentálása
Elérhető reportok:
- `server/test-results.txt`
- `server/test-results.xml`
- `server/test-results.html`

Javasolt beadási bizonyíték:
- kézi tesztfájl (`request.rest`)
- backend/frontend tesztkód
- futási kimenet vagy képernyőkép
- rövid összegzés (mit validál az adott teszt)
