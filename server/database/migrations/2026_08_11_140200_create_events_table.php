<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary')->nullable();
            $table->text('description')->nullable();

            // Обложка лежит колонкой, а не в медиатеке: она одна, её правят
            // и из админки, и из кабинета, и перевод её на коллекцию потребовал
            // бы отдельной сущности ради одного поля.
            $table->string('image_path')->nullable();

            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->nullable();

            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Цена живёт в мероприятии, а не в конфиге: на площадке у каждого
            // мастера своя.
            $table->decimal('price', 10, 2);
            $table->decimal('deposit_rate', 4, 3)->nullable();

            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
