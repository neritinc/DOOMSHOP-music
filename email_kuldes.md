# Email küldés – egyszerű magyarázat (kezdő szinten)

Ez a fájl röviden és közérthetően elmagyarázza, hogyan működik a vásárlás után küldött email sablonod, mit csinálnak a részei, és miért vannak ott.

A sablon itt van:
`server/resources/views/emails/purchase.blade.php`

Ez egy **Laravel Blade** sablon. A Blade egy olyan HTML-sablon rendszer, ahol az HTML-be be lehet szúrni változókat és egyszerű logikát (if/foreach), hogy az email tartalma dinamikusan változzon.

## Mi történik áttekintésben?
1. A szerver (Laravel) összeállítja az emailt, és megad neki adatokat (pl. vevő neve, megvásárolt termékek, letöltési linkek).
2. A Blade sablon ezeket az adatokat beilleszti az HTML-be.
3. Az emailkliens (pl. Gmail) megjeleníti az HTML-t.

## A sablon részei és szerepük

### 1) HTML váz
A fájl elején standard HTML szerkezet van:
- `<!doctype html>` és az `html`, `head`, `body` tagek
- Ezekre azért van szükség, mert az emailkliens egy HTML oldalként rendereli az emailt.

### 2) `head` – alap beállítások és stílus
Itt vannak a megjelenéshez szükséges dolgok:
- `meta charset` és `viewport`: helyes karakterkezelés és mobilos megjelenés.
- `title`: az email címkéje (nem minden kliensben látszik).
- `style`: az email dizájnja.

**Miért van ennyi CSS?**
- Az emailekben általában **inline vagy beágyazott CSS** a biztosabb.
- A sablon külön osztályokat használ (`.card`, `.header`, `.btn`, stb.), hogy szépen nézzen ki.

### 3) `body` – maga a tartalom
A valódi email tartalom itt van. A fő struktúra:
- `.wrapper`: külső térköz
- `.card`: egy középre igazított doboz
- `.header`: cím rész
- `.content`: főbb szöveg és a letöltési lista
- `.footer`: alsó, kisbetűs tájékoztatás

## Dinamikus részek (Blade szintaxis)

### Név beszúrása
```
Hi{{ isset($name) && $name !== '' ? ' ' . e($name) : '' }},
```
- Ha van név (`$name`), akkor kiírja: “Hi Peter,”
- Ha nincs név, akkor csak: “Hi,”
- `e($name)`: escapeli a szöveget, hogy biztonságos legyen (ne lehessen HTML-t beszúrni vele).

### Termékek listázása
```
@if (!empty($items))
  @foreach ($items as $item)
    ...
  @endforeach
@endif
```
- Ha van `items` lista, akkor végigmegy rajta, és minden elemhez kirajzol egy blokkon belül:
  - a típusát (pl. "track")
  - a címét
  - és a letöltési gombot (ha van link)

### Letöltési gomb
```
@if (!empty($item['url']))
  <a class="btn" href="{{ $item['url'] }}">Download</a>
@endif
```
- Ha van URL, akkor megjelenik egy gomb.
- A gomb valójában egy stilizált `<a>` link.

## Miért jó ez így?
- **Egyetlen sablon** kezeli az összes vásárlást, akár 1, akár 10 termék van.
- **Biztonságos**: a név escape-elve van.
- **Bővíthetőség**: ha új mezőt akarsz (pl. ár, formátum), csak a sablont és az adatokat kell bővíteni.

## Hogyan kap adatokat a sablon?
A Blade fájl csak a “megjelenítés”. A valódi adatok általában egy **Mailable** vagy email küldő osztályból jönnek, valami ilyesmi:
- `$name` – a vevő neve
- `$items` – lista, amiben minden elem egy termék/adat

Példa adatszerkezet:
```
$name = "Peter";
$items = [
  ["type" => "track", "title" => "Song A", "url" => "https://..."],
  ["type" => "album", "title" => "Album B", "url" => "https://..."],
];
```

## Hogyan készülnek a letöltési linkek és a “teljes zenei fájl”?
Ezt a részt a backend (Laravel) kezeli. A fő folyamat:

1. A kliens meghívja a `checkout` végpontot.
2. A `CheckoutController::checkout` összeszedi a kosár elemeit, és mindegyikhez **aláírt letöltési linket** generál.
3. Ezek a linkek mennek bele az emailbe (`emails.purchase` sablon).
4. Amikor a felhasználó a linkre kattint, a `DownloadController::download` szolgálja ki a tényleges fájlt (track) vagy ZIP‑et (album).

### Hol történik a linkek előállítása?
Fájl: `server/app/Http/Controllers/CheckoutController.php`

Fontos rész:
- `CheckoutController::checkout(...)` összerakja a `$downloadItems` listát.
- Itt hívja a `URL::temporarySignedRoute(...)` metódust, ami **lejáró, aláírt** linket ad:
  - `type` = `track` vagy `album`
  - `id` = a track/album azonosítója
  - `expiresAt` = lejárati idő (most + 7 nap)

### Melyik route hívódik meg?
Fájl: `server/routes/api.php`

- Checkout végpont:
  - `POST /api/my-carts/{id}/checkout`
  - Middleware: `auth:sanctum` + `ability:checkout:self:post`
  - Ez csak bejelentkezett userrel működik, és ez indítja az email küldést.

- Letöltési végpont:
  - `GET /api/download/{type}/{id}`
  - `type` csak `track` vagy `album` lehet
  - Middleware: `signed`
  - A route neve: `download.signed`

### Mit jelent a `signed` middleware?
Ez azt jelenti, hogy a link **aláírt**:
- A link tartalmaz egy `signature` és egy `expires` paramétert.
- Ha valaki módosítja a linket, vagy lejár az idő, a kérés **elutasításra** kerül.
- Így nem kell külön tokeneket tárolni az adatbázisban, mégis biztonságos a letöltés.

### Hol történik a fájlok kiszolgálása?
Fájl: `server/app/Http/Controllers/DownloadController.php`

Itt a fő belépési pont:
- `DownloadController::download(string $type, int $id)`

Ez dönti el, hogy:
- `track` esetén **egy fájlt** küld vissza,
- `album` esetén **ZIP‑et** készít és azt küldi vissza.

#### Track letöltés (teljes zenei fájl)
Fő lépések:
- `resolveTrackSourcePath(...)` megkeresi, hol van a valódi fájl a lemezen.
- `streamAudioFile(...)` **streamelve** küldi ki az MP3‑at (vagy ami a fájl).

#### Album letöltés (ZIP)
Fő lépések:
- `buildAlbumZip(...)` összegyűjti az album trackjeit.
- Létrehoz egy ideiglenes ZIP‑et.
- `response()->download(...)->deleteFileAfterSend(true)` elküldi, majd törli a ZIP‑et.

## Melyik függvény mit csinál?
Fájl: `server/app/Http/Controllers/DownloadController.php`

- `download($type, $id)`  
  Fő belépési pont a letöltéshez. Dönt a track vs album logikáról.

- `resolveTrackSourcePath($trackPath)`  
  Megkeresi a track fájl fizikai helyét több lehetséges útvonalon.

- `streamAudioFile($fullPath, $downloadName)`  
  A tényleges fájlküldés. Kezeli a böngésző “Range” kéréseit is (részleges letöltés).

- `buildAlbumZip($album)`  
  Trackekből ZIP‑et készít.

- `safeDownloadName($title, $extension)`  
  Biztonságos fájlnév készítés (tiltott karakterek nélkül).

## Gyakori módosítási pontok
- Szöveg cseréje: `Thanks for your purchase!` sor
- Gomb színe: `.btn { background: ... }`
- Új mezők: a `@foreach` blokkba új sorokat rakhatsz

## Ha valami nem jelenik meg
- Ellenőrizd, hogy a `$items` tényleg nem üres-e.
- Ellenőrizd, hogy a `$item['url']` nem üres-e.
- Ne felejtsd el, hogy az email kliensek néha levágják vagy felülírják a CSS-t.

---


