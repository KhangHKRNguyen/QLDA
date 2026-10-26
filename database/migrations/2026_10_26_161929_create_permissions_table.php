<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('permission_id');
            $table->string('permission_name', 100)->unique();
            $table->string('module', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};