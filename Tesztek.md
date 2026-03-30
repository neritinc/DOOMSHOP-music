# Tesztek

> Projekt: **DOOMSHOP-music**

## Tartalom
- [Kézi tesztelés (`request.rest`)](#kézi-tesztelés-requestrest)
- [Backend tesztek](#backend-tesztek)
- [Frontend tesztek](#frontend-tesztek)
- [Mintakódok](#mintakódok)
- [Teszteredmények dokumentálása](#teszteredmények-dokumentálása)

## Kézi tesztelés (`request.rest`)
Fájl: `server/request.rest`

Ellenőrzött területek:
- login / logout
- role alapú jogosultság (`admin` vs `customer`)
- tracks CRUD elérés
- usersme műveletek
- carts és my-carts

## Backend tesztek
Keretrendszer: PHPUnit

```bash
cd server
php artisan test
```

Példafájlok:
- `server/tests/Feature/UserTest.php`
- `server/tests/Feature/UserUpdateTest.php`
- `server/tests/Unit/UserTest.php`

## Frontend tesztek
Tesztek:
- Vitest (unit)
- Cypress (E2E)

```bash
cd client
npm run test:unit
npm run test:e2e
```

## Mintakódok
### 1) Kézi teszt (request.rest) login minta
```http
POST {{host}}/api/users/login
Accept: application/json
Content-Type: application/json

{
  "email": "admin@doomshoprecords.com",
  "password": "admin123"
}
```

### 2) Kézi teszt CRUD minta
```http
POST {{host}}/api/tracks
Accept: application/json
Content-Type: application/json
Authorization: Bearer {{admin_token}}

{
  "track_title": "Night Drive",
  "genre_name": "Synthwave"
}
```

### 3) Backend teszt minta (Feature)
```php
$response = $this->postJson('/api/users/login', [
    'email' => $email,
    'password' => $password,
]);

$response->assertStatus(200);
```

### 4) Backend validációs teszt minta
```php
$response = $this->patchJson("/api/users/{$admin->id}", [
    'role' => 2,
]);

$response->assertStatus(422);
```

### 5) Frontend unit teszt minta (Vitest)
```js
it('mounts renders properly', () => {
  const wrapper = mount(App, {
    global: { plugins: [createTestingPinia()] },
  });

  expect(wrapper.exists()).toBe(true);
});
```

### 6) Frontend E2E teszt minta (Cypress)
```js
it('Sikertelen login üres mezőkkel', () => {
  cy.get('button[type="submit"]').click();
  cy.get('form').should('have.class', 'was-validated');
});
```

## Teszteredmények dokumentálása
Elérhető reportok:
- `server/test-results.txt`
- `server/test-results.xml`
- `server/test-results.html`

Beadáskor javasolt:
- tesztfutás képernyőkép
- reportfájlok csatolása
- rövid összegzés (mi ment át / mi bukott)
