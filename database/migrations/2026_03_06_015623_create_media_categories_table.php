<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('media_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();

        DB::table('media_categories')->insert([
            ['name' => 'Videos', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Audio', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Photos', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Downloads', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_categories');
    }
};
