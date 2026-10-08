<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->dateTime('starts_at');
            $table->string('email')->nullable();
            $table->string('status', 20);
            $table->dateTime('confirmed_at')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('flyer_path')->nullable();
            $table->string('flyer_thumb_path')->nullable();
            $table->timestamps();

            $table->index(['status', 'starts_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
