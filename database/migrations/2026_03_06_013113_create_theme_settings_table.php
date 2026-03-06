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
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('church_name')->default('LFC Jahi');
            $table->string('logo_url')->nullable();
            $table->string('tagline')->nullable();
            $table->string('primary_color')->default('#0a4d68');
            $table->string('accent_color')->default('#f2994a');
            $table->string('font_family')->default('system-ui, -apple-system, Segoe UI, Roboto, sans-serif');
            $table->string('layout_style')->default('standard');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('theme_settings');
    }
};
