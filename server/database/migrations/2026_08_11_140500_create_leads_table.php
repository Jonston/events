<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Кто чей лид, определяет наша база по странице входа: атрибуция
            // мастера не зависит от рекламных окон и работает точно.
            $table->foreignId('master_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->string('source')->nullable();
            $table->string('status')->default('new')->index();

            // Даты старта здесь нет: её знает мероприятие, и окно возврата
            // считается от него.
            $table->timestamp('deposit_paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->string('client_ip', 45)->nullable();
            $table->string('client_user_agent', 512)->nullable();
            $table->string('landing_url', 2048)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
