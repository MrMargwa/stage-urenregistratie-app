<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('accent_color', 20)->nullable()->default('amber')->change();
        });

        DB::table('users')->whereNull('accent_color')->update(['accent_color' => 'amber']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('accent_color', 20)->nullable()->change();
        });
    }
};
