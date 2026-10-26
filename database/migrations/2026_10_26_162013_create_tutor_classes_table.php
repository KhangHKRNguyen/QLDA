<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_classes', function (Blueprint $table) {
            $table->bigIncrements('class_id');
            $table->string('subject', 100);
            $table->string('grade_level', 50);
            $table->string('learning_format', 50);
            $table->text('schedule')->nullable();
            $table->unsignedTinyInteger('sessions_per_week');
            $table->decimal('fee_per_session', 12, 2);
            $table->text('location')->nullable();
            $table->string('status', 30)->default('pending');
            $table->decimal('enrollment_fee', 12, 2)->default(0);
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->foreignId('person_id')
                ->constrained('people', 'person_id')
                ->restrictOnDelete();

            $table->foreignId('tutor_request_id')
                ->constrained('tutor_requests', 'tutor_request_id')
                ->restrictOnDelete();

            $table->foreignId('account_id')
                ->constrained('accounts', 'account_id')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_classes');
    }
};