# DOOMSHOP-music – működés leírás (frontend)

Ez a leírás a `client/` alatti Vue alkalmazás működését magyarázza: mi miért van, hogyan kapcsolódnak össze az API-hívások, a bejelentkezés, az admin jogosultságok, és a felületi komponensek.

**Gyors térkép a fontosabb részekhez**

- API kliens és szolgáltatások: `client/src/api/axiosClient.js`, `client/src/api/*.js`
- Auth állapot: `client/src/stores/userLoginLogoutStore.js`
- Toast üzenetek: `client/src/stores/toastStore.js`, `client/src/components/Message/ToastContanier.vue`
- Menüsáv és logout: `client/src/components/Layout/Menu.vue`
- Route-ok + jogosultság őr: `client/src/router/index.js`
- Példanézetek: `client/src/views/LoginView.vue`, `client/src/views/TracksView.vue`, `client/src/views/AdminCartsView.vue`

## 1) API réteg – miért van külön `apiClient` és külön service fájlok?

**Miért?**

- Az `apiClient` egy közös, egységes Axios példány. Itt állítjuk be egyszer a `baseURL`-t és a fejlécet.
- A service fájlok (`trackService.js`, `genreService.js`, stb.) csak endpointokat tartalmaznak. Ez átláthatóvá teszi, hogy egy adott funkció melyik backend útvonalat hívja.

**Hogyan?**

Az `apiClient` alap beállítása és interceptorai a `client/src/api/axiosClient.js` fájlban vannak.

Kiemelt részlet:

```js
const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

apiClient.interceptors.request.use((config) => {
  if (config.data instanceof FormData) {
    delete config.headers["Content-Type"];
  }
  const token = useUserLoginLogoutStore().token;
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

**Mit csinál?**

- Ha `FormData` megy (fájlfeltöltés), akkor törli a `Content-Type`-ot, hogy a böngésző állítsa be a boundary-t.
- Ha van bejelentkezett user (token), akkor hozzáadja az `Authorization: Bearer <token>` fejlécet.

**Hibakezelés és toast**

A response interceptor több típust kezel (422, 401, 500, hálózati hiba), és ez alapján hívja a toast store-t:

- 422: validációs hiba, nem dob toastot, a komponens kezeli.
- 401: toastban jelzi a hibát (pl. rossz jelszó).
- 500: speciális MySQL 1451 hiba esetén szebb szöveget ad.
- Hálózati hiba: „A szerver nem elérhető.”

Fájl: `client/src/api/axiosClient.js`

## 2) Auth folyamat – mi, miért és hogyan működik?

**Miért?**

Az auth állapotot globálisan kell kezelni, hogy minden komponens tudja, be van-e jelentkezve a felhasználó, admin-e, és mi a token.

**Hogyan?**

A `client/src/stores/userLoginLogoutStore.js` Pinia store a központi auth state:

- `item`: a backendtől kapott user adat (token + role + name).
- `localStorage`-be mentés, hogy frissítés után is megmaradjon a bejelentkezés.
- Getterek: `isLoggedIn`, `isAdmin`, `userNameWithRole`, `token`.

Példarészlet:

```js
const response = await service.login(data);
this.item = response.data;
localStorage.setItem("user_data", JSON.stringify(response.data));
```

**Belépés UI oldalon**

A bejelentkező nézet a `client/src/views/LoginView.vue`:

- Űrlap `email` és `password` mezőkkel.
- `submit()` meghívja a store `login` actionjét.
- Siker után `this.$router.push("/")`.

## 3) Jogosultság és route védelem

**Miért?**

Ne legyen elérhető admin tartalom nem admin felhasználónak.

**Hogyan?**

A router meta `roles` mezőket használ, és a `beforeEach` ellenőriz:

- `roles: [1]` csak admin (role = 1)
- `roles: [1, 2]` admin + customer
- Ha a user nincs bejelentkezve vagy nincs joga, átirányít `"/login"` vagy `"/"`.

Fájl: `client/src/router/index.js`

Kiemelt részlet:

```js
const raw = localStorage.getItem("user_data");
const user = raw ? JSON.parse(raw) : null;
const role = user?.role ?? null;

if (to.meta.roles && !to.meta.roles.includes(role)) {
  return next(role ? "/" : "/login");
}
```

## 4) Menü és logout logika

**Miért?**

A menünek is tudnia kell, ki van bejelentkezve, és admin-e.

**Hogyan?**

A `client/src/components/Layout/Menu.vue` Pinia store-ból olvas:

- `isLoggedIn`, `isAdmin`, `userNameWithRole`
- Ha admin, látszik az `All Carts` menüpont.
- Logout gomb `userLoginLogoutStore.logout()` hívás után visszanavigál `/`-ra.

## 5) API service fájlok – milyen funkciók vannak?

**Az alap elv**: minden erőforráshoz tartozik egy saját service, amely csak HTTP hívásokat tartalmaz.

- `client/src/api/userLoginLogoutService.js`
  - `login(data)` › `POST /users/login`
  - `logout()` › `POST /users/logout`
  - `getMeRefresh()` › `GET /usersme`

- `client/src/api/trackService.js`
  - `list()` › `GET /tracks`
  - `show(id)` › `GET /tracks/:id`
  - `analyzeUpload(file)` › `POST /tracks/analyze-upload` (`FormData`)
  - `create(payload)` › `POST /tracks` (`FormData` esetén fájlokkal)
  - `update(id, payload)` › ha `FormData`, `_method=PATCH` és `POST`
  - `destroy(id)` › `DELETE /tracks/:id`
  - `regeneratePreview(id)` › `POST /tracks/:id/regenerate-preview`

- `client/src/api/genreService.js`
  - `list()` › `GET /genres`
  - `create(payload)` › `POST /genres`

- `client/src/api/recommendationLinkService.js`
  - `list()` › `GET /recommendation-links`
  - `create(payload)` › `POST /recommendation-links`
  - `destroy(id)` › `DELETE /recommendation-links/:id`

- `client/src/api/liveshowLinkService.js`
  - `list()` › `GET /liveshow-links`
  - `create(payload)` › `POST /liveshow-links`
  - `destroy(id)` › `DELETE /liveshow-links/:id`

## 6) Példa: track létrehozás folyamata (Admin)

**Miért ilyen bonyolult?**

Track létrehozáskor fájlfeltöltés van (audio + borító), és opcionálisan új album/genre/artist létrehozás is. Ezért `FormData` kell.

**Hogyan?**

A `client/src/views/TracksView.vue` admin űrlapja:

- Ellenőrzi: van-e audio fájl, van-e legalább 1 artist és 1 genre.
- Ha új genre név van, elküldi `genre_names[]` formában.
- A track preview beállítások (start/end) max 30 sec.

Részlet a payload készítésből:

```js
const payload = new FormData();
payload.append("track_title", this.form.track_title);
...
payload.append("track_audio", this.audioFile);
artistNames.forEach((name, index) => payload.append(`artist_names[${index}]`, name));
```

A `trackService.create(payload)` elküldi ezt a backendnek.

## 7) Admin funkció példa

Konkrét admin nézet a projektben: `client/src/views/AdminCartsView.vue`.

Ez admin-only route, a routerben `roles: [1]` szerepel. A nézet egyszerűen lekéri az összes kosarat:

```js
const res = await service.allCarts();
this.carts = res.data || [];
```

Megjegyzés: jelenleg nincs `Adminview.vue` nevű fájl, hanem az admin funkciók külön nézetekben vannak (pl. `AdminCartsView.vue`, admin-only részek a `TracksView.vue`-ben).

## 8) Toast üzenetek – miért, hogyan?

A toast store (`client/src/stores/toastStore.js`) egy nagyon egyszerű, 3 másodperces értesítési logika:

- `messages[]` tárolja a szövegeket.
- `type` dönti el, hogy Success vagy Error stílust kap.
- `show()` 3 másodperc múlva automatikusan ürít.

A megjelenítés a `client/src/components/Message/ToastContanier.vue` fájlban történik.

## 9) Összefoglaló működési lánc (login + védett oldal)

1. Felhasználó belép a `LoginView.vue`-n.
2. A `userLoginLogoutStore.login()` elküldi a kérést a `userLoginLogoutService`-en keresztül.
3. A válaszból token kerül `localStorage`-be.
4. A következő API hívásoknál az `axiosClient` automatikusan hozzáadja az `Authorization` headert.
5. A router `beforeEach` ellenőrzi a `roles` mezőt, és csak megfelelő jogosultság esetén enged be.
6. A menüben admin esetén megjelennek az admin linkek.

---





Pontosan, mindkét út a tracks táblához vezet, de két különböző módon kapcsolódnak. Ez azért fontos, mert a rendszer két különböző igényt szolgál ki egyszerre:

1. A közvetlen kapcsolat (tracks.genre_id)
Ez a "Fő műfaj".

Mire jó? Gyorsan megmondja, mi a dal elsődleges stílusa.

Korlátja: Itt csak egyetlen azonosítót tárolhatsz. Egy dal nem lehet egyszerre 1-es (Rock) és 2-es (Pop) azonosítójú ebben az oszlopban.

2. A kapcsolótáblás út (track_genres)
Ez a "Több műfaj" (Many-to-Many) kapcsolat.

Mire jó? Itt bármennyi párosítást létrehozhatsz. Ugyanaz a track_id szerepelhet többször is, más-más genre_id-val.

Példa: A dalod lehet egyszerre Electronic, Synthwave és 80s.

Miért van mindkettő? (A te esetedben)
A korábban küldött PHP kódjaid alapján ez a kettősség azért van, mert:

Az adatbázisod rugalmas (több műfajt enged a kapcsolótáblán keresztül).

De a rendszerednek szüksége van egy fix CSV exportra is, ami valószínűleg egy egyszerűsített listát vár.

