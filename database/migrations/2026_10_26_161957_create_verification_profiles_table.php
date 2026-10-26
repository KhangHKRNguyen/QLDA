<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_profiles', function (Blueprint $table) {
            $table->bigIncrements('verification_profile_id');
            $table->string('document_type', 100);
            $table->string('attachment_file')->nullable();
            $table->string('review_status', 30)->default('pending');
            $table->text('processing_reason')->nullable();
            $table->date('supplement_deadline')->nullable();

            $table->foreignId('person_id')
                ->constrained('people', 'person_id')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_profiles');
    }
};