<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->bigIncrements('account_id');
            $table->string('username', 100)->unique();
            $table->string('password');

            $table->string('status', 30)->default('active');
            $table->text('lock_reason')->nullable();
            $table->timestamp('lock_expires_at')->nullable();
            $table->timestamp('status_updated_at')->nullable();

            $table->foreignId('role_id')
                ->constrained('roles', 'role_id')
                ->restrictOnDelete();

            $table->foreignId('person_id')
                ->constrained('people', 'person_id')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};