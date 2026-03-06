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
        Schema::create('media_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_category_id')->constrained('media_categories')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['media_category_id', 'name']);
        });

        $categories = DB::table('media_categories')->pluck('id', 'name');
        $now = now();

        $seedRows = [
            ['media_category_id' => $categories['Videos'] ?? null, 'name' => 'Sermons', 'created_at' => $now, 'updated_at' => $now],
            ['media_category_id' => $categories['Videos'] ?? null, 'name' => 'Event Videos', 'created_at' => $now, 'updated_at' => $now],
            ['media_category_id' => $categories['Audio'] ?? null, 'name' => 'Sermon Audio', 'created_at' => $now, 'updated_at' => $now],
            ['media_category_id' => $categories['Audio'] ?? null, 'name' => 'Podcasts', 'created_at' => $now, 'updated_at' => $now],
            ['media_category_id' => $categories['Photos'] ?? null, 'name' => 'Church Events', 'created_at' => $now, 'updated_at' => $now],
            ['media_category_id' => $categories['Photos'] ?? null, 'name' => 'Celebrations', 'created_at' => $now, 'updated_at' => $now],
            ['media_category_id' => $categories['Downloads'] ?? null, 'name' => 'Bulletins', 'created_at' => $now, 'updated_at' => $now],
            ['media_category_id' => $categories['Downloads'] ?? null, 'name' => 'Flyers', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('media_subcategories')->insert(array_values(array_filter($seedRows, fn ($row) => ! is_null($row['media_category_id']))));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_subcategories');
    }
};
