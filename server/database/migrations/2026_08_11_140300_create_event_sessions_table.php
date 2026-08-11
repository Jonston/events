<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // День и время хранятся раздельно, а не одним dateTime: расписание
            // редактируется по дням — перенос мероприятия двигает день целиком,
            // а не каждый пункт по отдельности.
            $table->date('day');
            $table->time('starts_at');
            $table->time('ends_at')->nullable();

            $table->string('title');
            $table->string('note')->nullable();

            $table->timestamps();

            // Расписание всегда читается целиком по мероприятию и в порядке
            // хода дня — сортировка отдана индексу, а не памяти.
            $table->index(['event_id', 'day', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
