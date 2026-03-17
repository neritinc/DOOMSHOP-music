# DOOMSHOP-music

Egyszeru, gyors lepesek a projekthez.

**Gyors setup (Windows)**

1. A projekt gyokerben futtasd:
```powershell
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

Ez ezt csinalja:
- telepiti a server es client fuggosegeket
- letrehozza a `server/.env`-et (ha nincs)
- general Laravel app key-t
- futtatja a migraciokat
- letrehozza a storage symlinket
- megprobalja megtalalni az `ffmpeg` / `ffprobe`-ot

**Inditas**

1. Backend:
```powershell
cd server
composer run dev
```

2. Frontend:
```powershell
cd client
npm run dev
```

Telefonos megnyitas:
```powershell
npm run dev -- --host
```

**Masik gepre koltozes (adatokkal egyutt)**

Vidd magaddal ezeket:
1. A repot
2. DB dumpot (`.sql`)
3. Media fajlokat: `server/storage/app/public/`
4. Preview mapping fajlt: `server/database/csv/track_previews.csv`

Majd:
1. Klonozo parancs + setup:
```powershell
git clone <your-repo-url>
cd DOOMSHOPRECORDS
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

2. `server/.env` beallitas:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doomshop
DB_USERNAME=root
DB_PASSWORD=your_password
```

3. DB visszatoltes:
```powershell
mysql -u root -p doomshop < C:\path\to\backup.sql
```

4. Media fajlok visszamasolasa:
`server/storage/app/public/` (tracks, previews, track-covers)

5. Laravel sync:
```powershell
cd server
php artisan storage:link
php artisan migrate
php artisan optimize:clear
```

Ha seedelesbol akarod ujraepiteni (torli a DB-t):
```powershell
php artisan migrate:fresh --seed
```

**GitHub commit media fajlokkal**

```powershell
cd server
powershell -ExecutionPolicy Bypass -File .\export-music-data.ps1
cd ..
git add .
git add server/storage/app/public/tracks
git add server/storage/app/public/previews
git add server/storage/app/public/track-covers
git add server/database/csv/track_previews.csv
git add server/database/backups
git commit -m "Update tracks, previews, covers, and mappings"
git push
```

Megjegyzesek:
- `server/public/storage` symlink, nem kell gitbe.
- Ha a media nagy, hasznalj Git LFS-t.
