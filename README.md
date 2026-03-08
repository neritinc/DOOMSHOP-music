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

## Move To Another PC (Keep Everything)

To keep all songs, covers, and custom previews, move these together:

1. Source code (repo)
2. Database dump (`.sql`)
3. Media files in `server/storage/app/public/`:
   - `tracks/`
   - `previews/`
   - `track-covers/`
4. Preview mapping file: `server/database/csv/track_previews.csv`

### 1) Clone and setup

```powershell
git clone <your-repo-url>
cd DOOMSHOPRECORDS
powershell -ExecutionPolicy Bypass -File .\setup.ps1
```

### 2) Configure `server/.env`

`server/.env` must point to the MySQL database on the new machine:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=doomshop
DB_USERNAME=root
DB_PASSWORD=your_password
```

If needed:

```powershell
cd server
copy .env.example .env
php artisan key:generate
```

### 3) Restore database

Import your SQL dump with dbForge/phpMyAdmin, or CLI:

```powershell
mysql -u root -p doomshop < C:\path\to\backup.sql
```

### 4) Restore media files

If media is not in git (or incomplete), copy back:

`server/storage/app/public/` (tracks, previews, track-covers)

### 5) Laravel sync

```powershell
cd server
php artisan storage:link
php artisan migrate
php artisan optimize:clear
```

If you want to rebuild from seed files (not from SQL import), run:

```powershell
php artisan migrate:fresh --seed
```

Note: `migrate:fresh --seed` wipes imported DB data and rebuilds from seed/CSV.

### 6) Frontend

```powershell
cd client
npm install
npm run dev
```

## GitHub Commit With Songs/Uploads Included

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

Notes:
- `server/public/storage` is a symlink and is not required in git.
- If files become very large, use Git LFS for media files.




TELEFONOS MEGNYITÁS 

npm run dev -- --host