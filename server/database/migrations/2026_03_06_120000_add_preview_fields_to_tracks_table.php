<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            if (!Schema::hasColumn('tracks', 'preview_start_at')) {
                $table->integer('preview_start_at')->default(0)->after('track_path');
            }

            if (!Schema::hasColumn('tracks', 'preview_end_at')) {
                $table->integer('preview_end_at')->default(30)->after('preview_start_at');
            }

            if (!Schema::hasColumn('tracks', 'preview_path')) {
                $table->string('preview_path')->nullable()->after('preview_end_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tracks', function (Blueprint $table) {
            $columns = [];

            if (Schema::hasColumn('tracks', 'preview_path')) {
                $columns[] = 'preview_path';
            }

            if (Schema::hasColumn('tracks', 'preview_end_at')) {
                $columns[] = 'preview_end_at';
            }

            if (Schema::hasColumn('tracks', 'preview_start_at')) {
                $columns[] = 'preview_start_at';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
