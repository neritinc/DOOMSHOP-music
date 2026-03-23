# Zene feltöltés és 30 mp-es előnézet

Ez a leírás arról szól, hogyan működik a zene feltöltése ebben a projektben, hogyan készül belőle egy legfeljebb 30 mp-es előnézet (preview), és miért tudja a vásárló csak az előnézetet lejátszani, míg az admin a teljes dalt is.

---

**Gyors útvonal‑térkép (fájlok)**

Frontend (Vue):
- `client/src/views/TracksView.vue` – admin feltöltő űrlap + preview beállítás
- `client/src/api/trackService.js` – API hívások (feltöltés, analyze, preview)

Backend (Laravel):
- `server/routes/api.php` – útvonalak (`/tracks`, `/tracks/{id}/preview`, `/tracks/{id}/source`)
- `server/app/Http/Controllers/TrackController.php` – feltöltés, preview generálás, lejátszás
- `server/app/Http/Requests/StoreTrackRequest.php` – validáció (30 mp limit)
- `server/app/Jobs/GenerateTrackPreview.php` – ffmpeg vágja a 30 mp‑et
- `server/app/Models/Track.php` – `preview_path`, `preview_start_at`, `preview_end_at`

---

## 1) Admin feltöltés – mi történik a böngészőben?

Az admin a `TracksView.vue` oldalon tölti fel az audio fájlt.

Fontos lépések:
1. Kiválaszt egy audio fájlt (`track_audio`).
2. Beállítja az előnézet kezdetét és végét:
   - `preview_start_at`
   - `preview_end_at`
3. A frontend kiszámolja, hogy a preview hossza maximum 30 mp lehet.

Itt látod a 30 mp limitet a frontend oldalon:
- `previewDuration` és `if (this.previewDuration > 30)` – `TracksView.vue`

Feltöltéskor a frontend `FormData`-t küld:
```js
payload.append("preview_start_at", String(this.form.preview_start_at));
payload.append("preview_end_at", String(this.form.preview_end_at));
payload.append("track_audio", this.audioFile);
```
Fájl: `client/src/views/TracksView.vue`

---

## 2) Mit csinál a backend a feltöltéskor?

A backend endpoint: `POST /tracks`
Fájl: `server/routes/api.php`

### 2.1 Validáció (max 30 mp)
A backend újra ellenőrzi, hogy a preview **ne legyen hosszabb 30 mp‑nél**:
```php
if (($end - $start) > 30) {
    $validator->errors()->add('preview_end_at', 'Preview duration can be at most 30 seconds.');
}
```
Fájl: `server/app/Http/Requests/StoreTrackRequest.php`

Ez azért fontos, mert:
- a frontendet bárki meg tudja kerülni,
- a backend az “utolsó kapu”.

### 2.2 Eredeti audio mentése
Amikor bejön a fájl, a backend elmenti a teljes dalt a `storage` alatt:
```php
$trackPath = $this->storeUploadedAudioFile($audioFile);
```
Fájl: `server/app/Http/Controllers/TrackController.php`

Az elmentett útvonal megy a DB‑be `track_path` mezőbe.

---

## 3) A 30 mp‑es preview generálása (ffmpeg)

Miután a track létrejön, a backend levágja a preview‑t a teljes dalból.

Ez itt történik:
```php
(new GenerateTrackPreview($track))->handle();
```
Fájl: `server/app/Http/Controllers/TrackController.php`

### A vágás logikája (ffmpeg)
```php
-ss <preview_start_at>
-t <preview_end_at - preview_start_at>
```
Fájl: `server/app/Jobs/GenerateTrackPreview.php`

Ez létrehoz egy új mp3 fájlt a `storage/app/public/previews/` mappába, például:
```
previews/preview_12_7b0c3a1e.mp3
```
Ezt az útvonalat a DB‑ben `preview_path` mezőben tároljuk.

---

## 4) Ki mit tud lejátszani?

### Vásárló / publikus user – csak preview
Az előnézet lejátszása:
```
GET /tracks/{id}/preview
```
Fájl: `server/app/Http/Controllers/TrackController.php`

Ez csak a preview mp3‑at streameli vissza, nem a teljes dalt.

### Admin – teljes dal is elérhető
Admin esetén a teljes dal innen jön:
```
GET /tracks/{id}/source
```
Fájl: `server/app/Http/Controllers/TrackController.php`

Ez a teljes audio fájlt streameli.

### Hogyan kap admin teljes track linket?
Amikor a backend visszaadja a track adatokat, **nem‑admin** felhasználónál ezt csinálja:
```php
if (! $isAdmin) {
    $payload['track_path'] = null;
}
```
Fájl: `server/app/Http/Controllers/TrackController.php`

Vagyis:
- **admin látja** a `track_path`-ot,
- **vásárló nem kapja meg** a teljes track útvonalat.

---

## 5) Összefoglaló folyamat (röviden)

1. Admin feltölt audio fájlt (`track_audio`) és beállítja a preview tartományt.
2. Backend elmenti a teljes dalt (`track_path`).
3. Backend ffmpeg‑gel levágja a 30 mp‑et (`preview_path`).
4. Vásárló csak `/preview`-t kap (előnézet).
5. Admin le tudja játszani a teljes tracket (`/source`).

---
Röviden: az ffmpeg egy parancssoros eszköz hang‑ és videófájlok vágására/átalakítására. Ebben a projektben azért kell, hogy a feltöltött teljes zenéből automatikusan kivágjon egy 30 mp‑es előnézetet, amit a vásárló lejátszhat.

Konkrétan itt használjuk:

server/app/Jobs/GenerateTrackPreview.php
Ott az ffmpeg parancs:

-ss → a vágás kezdete (preview_start_at)
-t → a vágás hossza (preview_end_at − preview_start_at)
Eredmény:

létrejön egy rövid mp3 fájl a storage/app/public/previews/ mappában,
ennek az útvonala kerül a DB‑be preview_path mezőbe,
a vásárló ezt a rövid fájlt kapja /tracks/{id}/preview végponton.
Ha kéred, hozzáírom a magyarázatot a music_upload.md‑hez is.



