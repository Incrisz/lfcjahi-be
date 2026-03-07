<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_cell_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->text('minister')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['home_cell_zone_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_cells');
    }
};
