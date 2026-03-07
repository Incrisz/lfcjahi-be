<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('speakers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        $now = now();
        $rows = DB::table('media_items')
            ->select('speaker')
            ->whereNotNull('speaker')
            ->where('speaker', '!=', '')
            ->distinct()
            ->orderBy('speaker')
            ->get();

        foreach ($rows as $row) {
            DB::table('speakers')->insert([
                'name' => $row->speaker,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('speakers');
    }
};
