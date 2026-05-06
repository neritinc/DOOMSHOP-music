# A tesztekhez végzett kód, valamint a teszteredmények dokumentációja

## Kézi teszt: pingelés (request rest)

### request.rest szerkezete
Fájl: `server/request.rest`

Szerkezeti elemek:
- Környezeti változók (`@host`, `@admin_token`, `@customer_token`)
- Egymásra épülő kérések `@name` hivatkozásokkal
- Jogosultsági mátrix tesztek (admin/customer)
- Login -> CRUD -> logout folyamat

### Bejelentkezés
```http
POST {{host}}/api/users/login
Accept: application/json
Content-Type: application/json

{
  "email": "admin@doomshoprecords.com",
  "password": "admin123"
}
```

### Minta kód CRUD műveletekre
```http
### Create (admin)
POST {{host}}/api/tracks
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "track_title": "Night Drive",
  "genre_name": "Synthwave"
}

### Read
GET {{host}}/api/tracks
Accept: application/json

### Update
PATCH {{host}}/api/users/{{customer_id}}
Authorization: Bearer {{admin_token}}
Content-Type: application/json

{
  "city": "Chicago"
}

### Delete
DELETE {{host}}/api/my-cart-items/{{my_item_id}}
Authorization: Bearer {{customer_token}}
```

## Backend tesztek

### Unit tesztek
- `server/tests/Unit/ExampleTest.php`
- `server/tests/Unit/UserTest.php`

### Funkcionális tesztek
- `server/tests/Feature/UserTest.php`
- `server/tests/Feature/UserUpdateTest.php`
- `server/tests/Feature/CustomerCartFlowTest.php`

### End-point tesztek
Az API endpointok viselkedését a feature tesztek és a `request.rest` együtt ellenőrzik:
- autentikáció (`/users/login`, `/users/logout`)
- jogosultság (`ability:admin`, self endpointok)
- CRUD endpointok (`tracks`, `genres`, `artists`, `carts`, `cart-items`)

### Backend tesztek futtatása
```bash
cd server
php artisan test
```

### Teszt lefutási képernyőkép dokumentálása
A repo tartalmaz mentett futási eredményeket:
- `server/test-results.txt`
- `server/test-results.html`

Javaslat képernyőképekhez:
1. Terminál futás zöld tesztekkel
2. Sikertelen teszt példa és javítás utáni újrafuttatás

## Frontend tesztek

### Teszt fajta megnevezése: Vitest, E2E test (Cypress)
- Unit/component: Vitest
- End-to-end: Cypress

### Futtatás
```bash
cd client
npm run test:unit
npm run test:e2e
```

### Néhány tipikus teszt mintakód, magyarázat
Vitest (komponens mount):
```js
import { mount } from '@vue/test-utils'
import App from '@/App.vue'

it('App komponens betöltődik', () => {
  const wrapper = mount(App)
  expect(wrapper.exists()).toBe(true)
})
```

Cypress (űrlap validáció):
```js
it('Sikertelen login üres mezőkkel', () => {
  cy.visit('/login')
  cy.get('button[type="submit"]').click()
  cy.get('form').should('have.class', 'was-validated')
})
```

### A teszt eredményének dokumentálása
- Backend teszteredmény fájlban: `server/test-results.txt`, `server/test-results.html`
- Frontend esetén javasolt:
  - Vitest kimenet mentése fájlba (`npm run test:unit > vitest-results.txt`)
  - Cypress report vagy screenshot/video mentés a Cypress output könyvtárból
