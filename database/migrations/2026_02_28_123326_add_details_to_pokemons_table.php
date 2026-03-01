<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pokemons', function (Blueprint $table) {
            $table->unsignedSmallInteger('height')->nullable()->after('api_url');
            $table->unsignedSmallInteger('weight')->nullable()->after('height');
            $table->string('dream_world_svg')->nullable()->after('weight');
        });
    }

    public function down(): void
    {
        Schema::table('pokemons', function (Blueprint $table) {
            $table->dropColumn(['height', 'weight', 'dream_world_svg']);
        });
    }
};
