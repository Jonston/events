<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Мероприятие, после которого написан отзыв. Необязательное:
            // к мастеру приходят и на консультацию по заявке без даты.
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();

            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();

            // Отзыв виден на сайте только после проверки: площадка отвечает
            // за то, что написано о мастере на его же странице.
            $table->string('status', 20)->index();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            // Один отзыв на мастера от человека. Второй — это правка первого,
            // иначе рейтинг накручивается повторной отправкой формы.
            $table->unique(['master_id', 'user_id']);
            $table->index(['master_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
