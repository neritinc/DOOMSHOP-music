<?php
//Névtér: Ennek a segítségével fogjuk elérni
namespace App\Helpers;

use Illuminate\Support\Facades\File; // Ha a File Facade-et akarod használni a natív PHP helyett

class CsvReader
{
    private static function normalizeHeader(array $header): array
    {
        return array_map(function ($h) {
            $h = (string) $h;
            $h = preg_replace('/^\xEF\xBB\xBF/', '', $h);
            return trim($h, " \t\n\r\0\x0B\"'");
        }, $header);
    }

    public static function csvToArray(string $fileName, string $delimiter = ';'): array
    {
        $filePath = database_path(path: $fileName);
        if (!File::exists($filePath)) {
            $filePath = storage_path('app/import/' . basename($fileName));
        }
        $data = [];

        if (!File::exists($filePath)) {
            return $data;
        }

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle, 0, $delimiter);
            $header = is_array($header) ? self::normalizeHeader($header) : [];

            while (($cols = fgetcsv($handle, 0, $delimiter)) !== false) {
                if ($header && count($header) === count($cols)) {
                    $data[] = array_combine($header, $cols);
                }
            }
            
            fclose($handle);
        }

        return $data;
    }
}
