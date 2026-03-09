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
        Schema::table('media_items', function (Blueprint $table): void {
            $table->unsignedBigInteger('download_count')->default(0)->after('media_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_items', function (Blueprint $table): void {
            $table->dropColumn('download_count');
        });
    }
};
