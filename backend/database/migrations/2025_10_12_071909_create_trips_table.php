<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('destination');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // ✅ DODAJ
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // ✅ ZOSTAW
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
