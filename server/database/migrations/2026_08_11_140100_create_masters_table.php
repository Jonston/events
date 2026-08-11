<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('masters', function (Blueprint $table) {
            $table->id();

            // Аккаунт владельца. Nullable: карточку может завести и
            // администратор — например, перенося мастера со старой площадки, —
            // а владелец подключит к ней свой вход позже.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('slug')->unique();
            $table->string('name');
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();

            // Портрет под круглый аватар и широкий снимок для шапки страницы.
            // Разные поля: растянуть портрет на всю ширину шапки значило бы
            // показать лицо крупным планом вместо того, чем мастер занимается.
            $table->string('photo_path')->nullable();
            $table->string('cover_path')->nullable();

            // Категорий у мастера пока нет: каталог один на всех, и делить его
            // на разделы до того, как в нём наберётся достаточно людей, значит
            // показывать посетителю три пустые полки вместо одной непустой.
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('masters');
    }
};
