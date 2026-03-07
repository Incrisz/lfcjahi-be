<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('coverage_areas')->nullable();
            $table->json('home_cell_pastors')->nullable();
            $table->text('home_cell_minister')->nullable();
            $table->text('outreach_pastor')->nullable();
            $table->text('outreach_minister')->nullable();
            $table->text('outreach_location')->nullable();
            $table->timestamps();

            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
