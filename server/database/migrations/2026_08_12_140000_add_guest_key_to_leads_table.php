<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Метка браузера, оставившего заявку.
 *
 * Вошедшего мы узнаём по аккаунту, а гостя — только по тому, что он нам
 * сам о себе принёс. Ключ живёт в его браузере и позволяет не принимать
 * вторую заявку на то же мероприятие: не защита от злого умысла,
 * а защита от второго нажатия кнопки.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('guest_key', 64)->nullable()->after('user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('guest_key');
        });
    }
};
