# DOOMSHOP-music

## Quick Setup (Windows)

Run one command from the project root:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

What it does:
- installs server/client dependencies
- creates `server/.env` (if missing)
- generates Laravel app key
- runs migrations
- creates storage symlink
- auto-detects `ffmpeg` / `ffprobe` and writes them into `server/.env` when found

## Moving To Another PC (Full Project Works)

To have everything work on another machine, copy/push all of these:
- source code
- database data
- uploaded media files from `server/storage/app/public/`
  - `tracks/`
  - `previews/`
  - `track-covers/`

Then on the new machine run:

```powershell
powershell -ExecutionPolicy Bypass -File .\setup.ps1
cd server
php artisan optimize:clear
```

Also verify:

```powershell
ffmpeg -version
ffprobe -version
```

## GitHub Commit With Songs/Uploads Included

If you want songs, previews, and covers to be uploaded to GitHub too, include storage files in commit:

```powershell
cd server
powershell -ExecutionPolicy Bypass -File .\export-music-data.ps1
cd ..
git add .
git add server/storage/app/public/tracks
git add server/storage/app/public/previews
git add server/storage/app/public/track-covers
git add server/database/backups
git commit -m "Update tracks and media files"
git push
```

Note:
- `server/public/storage` is a symlink and is not required in git.
- If files become very large, use Git LFS for media files.


Repo klónozás után másold át ezt a backup mappát az új gépre:
server/database/backups/storage_only_20260307_150307 (benne a storage/app/public tartalom)
Állítsd be a szerver oldalt:
cd server
cp .env.example .env (vagy másold a régi .env-ed)
.env-ben DB adatok kitöltése
composer install
php artisan key:generate
php artisan migrate
php artisan storage:link
Adatbázis adatok visszatöltése:
importáld a DB dumpot (doomshop_full.sql) DBForge/phpMyAdmin-ban
ha még nincs dumpod, mostani gépen exportáld és azt vidd át
Médiafájlok visszamásolása:
backupból másold az új gépen ide:
server/storage/app/public/ (tracks, previews, track-covers, stb.)
Frontend:
cd ../client
npm install
npm run dev
Ha ez megvan, ugyanazokat az adatokat fogod látni (trackek, előadók, preview-k, képek).