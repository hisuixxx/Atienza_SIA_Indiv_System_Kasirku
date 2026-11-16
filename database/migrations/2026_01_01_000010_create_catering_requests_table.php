<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone', 30);
            $table->string('customer_email');
            $table->string('event_name');
            $table->dateTime('event_date');
            $table->text('event_location');
            $table->integer('number_of_guests');
            $table->string('preferred_coffee_package');
            $table->text('additional_instructions')->nullable();
            $table->enum('status', [
                'submitted',
                'under_review',
                'quoted',
                'confirmed',
                'completed',
                'declined'
            ])->default('submitted')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catering_requests');
    }
};
