# Feltoltes (audio + cover) mukodese

Ez a doksi a track feltoltes (admin) teljes folyamatahoz keszult: kliens UI, API endpointok, validacio, tarolas, preview generalas, es 422 hibakezeles.

## Attekintes

A feltoltes 2 lepcsos:

1. **Analyze upload**: a kivalasztott audio fajlt a kliens felkuldI az elemzo endpointnak, amely metaadatokat (cim, eloado, hossz, BPM, beagyazott cover) probal kinyerni.
2. **Create track**: a felhasznalo altal szerkesztett adatok + audio/cover fajl kuldese a track letrehozas endpointnak.

Mindket lepes `multipart/form-data` kerest hasznal.

## Frontend folyamat (client)

Fajl valasztas:
- `client/src/views/TracksView.vue` kezeli az audio drag&drop + file inputot.
- `handleAudioSelected()` betolti a fajlt, megprobalja a track hosszt kinyerni (HTML5 audio metadata), es elinditja az analyze lepest.

Analyze lepes:
- Kliens oldal: `client/src/api/trackService.js` -> `analyzeUpload(file)`
- Endpoint: `POST /api/tracks/analyze-upload`
- Auth: admin token kotelezo.

Create lepes:
- Kliens oldal: `client/src/views/TracksView.vue` -> `createTrack()`
- Endpoint: `POST /api/tracks`
- Auth: admin token kotelezo.

## Analyze upload reszletek (backend)

Fajl ellenorzes:
- Request mez?: `track_audio`
- Max meret: 1048576 KB (~1 GB) a backend validacio szerint
- Engedelyezett kiterjesztes: `mp3`, `wav`, `ogg`, `m4a`, `flac`

Az endpoint muveletei:
- Ellenorzi a PHP upload hibakat (pl. `UPLOAD_ERR_INI_SIZE`, `UPLOAD_ERR_NO_TMP_DIR`).
- Validalja, hogy `track_audio` file meg van-e adva.
- FFMpeg/FFProbe segitsegevel beolvas:
  - duration (track_length_sec)
  - ID3 tag-ek (title, artist, genre, date, bpm)
  - embedded cover (base64 data url)
- Ha nincs eleg metaadat, fallback-kent fajlnevbol probal artist/title parost kinyerni.

Sikeres valasz (200):
- `data.track_title`
- `data.artist_names` (tomb)
- `data.genre_name`
- `data.release_date`
- `data.track_length_sec`
- `data.bpm_value`
- `data.cover_data_url`

## Create track reszletek (backend)

Fajl mezok:
- `track_audio` (audio file)
- `track_cover_file` (image file)

Fo mezok:
- `track_title` (kotelezo)
- `artist_names[]` vagy `artist_ids[]` (legalabb egy)
- `genre_names[]` vagy `genre_ids[]` (legalabb egy)
- `album_id` vagy `album_title` (opcionalis)
- `preview_start_at`, `preview_end_at` (max 30 sec ablak)
- `track_length_sec`, `track_price_eur`, `bpm_value`, `release_date` (opcionalis)

Tarolas:
- Audio: `storage/app/public/tracks/...`
- Cover: `storage/app/public/track-covers/...`
- Preview: `storage/app/public/previews/...` (ffmpeg generalja)

Preview generalas:
- Ha van audio es a preview ablak > 0, akkor a backend previewt general.
- Ha a preview generalas nem sikerul, a backend 422-es hibaval ter vissza.

## 422 hibakezeles (kliens)

Kozponti elv:
- A 422-es valasz validacios hiba, ezt nem global toastban kezeljuk.
- A `TracksView` megjeleniti a mezok alatti validacios uzeneteket.

Implementacio:
- `client/src/views/TracksView.vue`:
  - `validationErrors` tarolja az egyes mezok hibauzenetet.
  - `setValidationErrorsFromResponse()` kimasolja a backend `errors` objektumot.
  - Upload specifikus hibak (pl. PHP upload error) a `data.upload_error` mezoben jonnek, ezt a `track_audio` mezore irja ki.
  - A mezok alatt lathato hibak:
    - `track_title`
    - `genre_name` / `genre_ids.*`
    - `artist_names` / `artist_ids.*`
    - `preview_end_at`
    - `track_audio`
    - `track_cover_file`

Tipikus 422 okok:
- Hianyzik az audio fajl
- Nem tamogatott fajl tipus
- Tulsagosan nagy fajl
- Hibas preview start/end (tul hossz?)
- Ugyanolyan track title mar letezik
- Missing genre vagy artist
- Preview generalas hiba (ffmpeg nincs, vagy hibas config)

## Gyors teszt (curl)

Analyze:
```
curl -X POST http://127.0.0.1:8000/api/tracks/analyze-upload \
  -H "Authorization: Bearer <token>" \
  -F "track_audio=@C:\path\to\file.mp3"
```

Create:
```
curl -X POST http://127.0.0.1:8000/api/tracks \
  -H "Authorization: Bearer <token>" \
  -F "track_title=Test" \
  -F "artist_names[0]=Artist" \
  -F "genre_names[0]=Genre" \
  -F "preview_start_at=0" \
  -F "preview_end_at=30" \
  -F "track_audio=@C:\path\to\file.mp3"
```
