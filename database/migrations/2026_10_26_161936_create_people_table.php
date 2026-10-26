<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->bigIncrements('person_id');
            $table->string('full_name', 150);
            $table->string('phone_number', 20)->nullable();
            $table->string('email', 255)->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('district', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};