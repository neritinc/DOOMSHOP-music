# Dokumentáció

> Projekt: **DOOMSHOP-music**

## Tartalom
- [A szoftver célja](#a-szoftver-célja)
- [Használat röviden](#használat-röviden)
- [Vizsgakövetelmény megfelelés](#vizsgakövetelmény-megfelelés)
- [Technikai leírás](#technikai-leírás)
- [Minták és kódrészletek](#minták-és-kódrészletek)
- [Forráslista](#forráslista)

## A szoftver célja
A DOOMSHOP-music egy online zenei webshop, ahol:
- a felhasználók zenéket böngésznek, kosárba tesznek, vásárolnak és letöltenek,
- az adminisztrátorok teljes katalóguskezelést végeznek.

## Használat röviden
1. Regisztráció vagy bejelentkezés: `/registration`, `/login`
2. Katalógus böngészés: `/tracks`, `/artists`, `/genres`, `/albums`
3. Track adatlap + preview: `/tracks/:id`
4. Kosárkezelés: `/my-cart`
5. Checkout + letöltés

## Vizsgakövetelmény megfelelés
- [x] Életszerű probléma
- [x] CRUD: `users`, `tracks`, `artists`, `genres`, `albums`, `carts`, `cart_items`
- [x] RESTful backend + frontend
- [x] Autentikáció és jogosultságkezelés
- [x] Dokumentáció és tesztelés

## Technikai leírás
### Adatbázis
- MySQL + Laravel migrációk/seederek
- Kötelező fájlok: `Diagram.png`, `AdatbazisBackup.sql`

### Backend
- Laravel 12, Sanctum, FFmpeg/FFprobe
- Fő route fájl: `server/routes/api.php`
- Validáció: `server/app/Http/Requests/`

### Frontend
- Vue 3, Vite, Pinia, Vue Router, Axios, Bootstrap
- Belépési pont: `client/src/main.js`, `client/src/App.vue`
- Jogosultság: route meta `roles` + guard

## Minták és kódrészletek
### 1) Migráció minta (Laravel)
```php
Schema::create('tracks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('genre_id')->constrained('genres', 'genre_id');
    $table->string('track_title');
    $table->integer('bpm_value')->nullable();
    $table->date('release_date')->nullable();
    $table->integer('track_length_sec')->nullable();
    $table->timestamps();
});
```

### 2) Seeder minta
```php
public function run(): void
{
    $this->call([
        UserSeeder::class,
        GenreSeeder::class,
        ArtistSeeder::class,
        AlbumSeeder::class,
        TrackSeeder::class,
    ]);
}
```

### 3) Backend endpoint minta
```php
Route::post('tracks', [TrackController::class, 'store'])
    ->middleware(['auth:sanctum', 'ability:admin']);
```

### 4) Validáció minta (422)
```php
$validated = $request->validate([
    'track_audio' => 'required|file|max:1048576',
]);
```

### 5) Frontend service minta (Axios)
```js
export default {
  list() {
    return apiClient.get('/tracks');
  },
  create(payload) {
    return apiClient.post('/tracks', payload);
  },
};
```

### 6) Route guard minta (Vue Router)
```js
router.beforeEach((to, from, next) => {
  const raw = localStorage.getItem('user_data');
  const user = raw ? JSON.parse(raw) : null;
  const role = user?.role ?? null;

  if (to.meta.roles && !to.meta.roles.includes(role)) {
    return next(role ? '/' : '/login');
  }
  next();
});
```

### 7) Felületi minta (Vue)
```vue
<RouterLink class="btn btn-primary-clean" to="/tracks">
  Open Tracks
</RouterLink>
```

## Forráslista
- Laravel: https://laravel.com/docs
- Vue: https://vuejs.org/
- Vue Router: https://router.vuejs.org/
- Pinia: https://pinia.vuejs.org/
- Axios: https://axios-http.com/
- Bootstrap: https://getbootstrap.com/
