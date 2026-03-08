<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table): void {
            $table->decimal('track_price_eur', 8, 2)->default(1.99)->after('track_length_sec');
        });

        DB::table('tracks')->update(['track_price_eur' => 1.99]);
    }

    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table): void {
            $table->dropColumn('track_price_eur');
        });
    }
};
