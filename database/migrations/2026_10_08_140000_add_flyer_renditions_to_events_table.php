<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('flyer_2x_path')->nullable()->after('flyer_path');
            $table->unsignedSmallInteger('flyer_width')->nullable()->after('flyer_thumb_path');
            $table->unsignedSmallInteger('flyer_height')->nullable()->after('flyer_width');
            $table->unsignedSmallInteger('flyer_2x_width')->nullable()->after('flyer_height');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['flyer_2x_path', 'flyer_width', 'flyer_height', 'flyer_2x_width']);
        });
    }
};
