<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_requests', function (Blueprint $table) {
            $table->bigIncrements('tutor_request_id');
            $table->string('subject', 100);
            $table->string('grade_level', 50);
            $table->string('learning_format', 50);
            $table->text('schedule')->nullable();
            $table->unsignedTinyInteger('sessions_per_week');
            $table->decimal('desired_tuition_fee', 12, 2)->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->foreignId('person_id')
                ->constrained('people', 'person_id')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_requests');
    }
};