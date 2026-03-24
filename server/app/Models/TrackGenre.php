<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class TrackGenre extends Pivot
{
    public $timestamps = false;

    protected $table = 'track_genres';

    protected static function booted(): void
    {
        static::saved(function (): void {
            Track::syncCsvExports();
        });

        static::deleted(function (): void {
            Track::syncCsvExports();
        });
    }
}



// Ez a fájl egy összekötő tábla (pivot) modellje, ami a zeneszámok (Track) és stílusok (Genre) közötti kapcsolatot kezeli.

// A lényege: automatikus frissítés.

// Figyelés: Amint egy zeneszámhoz hozzáadsz egy új stílust, vagy törölsz egyet onnan...

// Akció: ...a kód azonnal meghívja a Track::syncCsvExports() függvényt.

// Cél: Ez biztosítja, hogy a rendszerből kiexportálható CSV fájlok (például egy dallista) mindig a legfrissebb stílusadatokat tartalmazzák, ne maradjanak benne régi vagy hibás adatok.

// Röviden: Ha változik a műfaj-besorolás, a rendszer rögtön újragenerálja a hozzá tartozó exportfájlokat.

// Szeretnéd, hogy megnézzük, mi történik pontosan a syncCsvExports() függvényen belül?
