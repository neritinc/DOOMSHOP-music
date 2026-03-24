<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('tracks')
    ->select('id','track_path')
    ->where('track_path','like','%.bin')
    ->get();

foreach ($rows as $row) {
    echo $row->id . " | " . $row->track_path . PHP_EOL;
}
