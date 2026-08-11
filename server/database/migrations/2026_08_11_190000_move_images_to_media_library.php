<?php

declare(strict_types=1);

use App\Models\Event;
use App\Models\Master;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Переезд картинок с колонок-путей на media library.
 *
 * Уже загруженные файлы переносятся, а не бросаются: колонки удаляются
 * последними, когда переносить уже нечего.
 */
return new class extends Migration
{
    /** Куда что переносим: таблица, колонка, модель, коллекция. */
    private const MOVES = [
        ['masters', 'photo_path', Master::class, 'photo'],
        ['masters', 'cover_path', Master::class, 'cover'],
        ['events', 'image_path', Event::class, 'image'],
    ];

    public function up(): void
    {
        foreach (self::MOVES as [$table, $column, $model, $collection]) {
            $this->move($table, $column, $model, $collection);
        }

        Schema::table('masters', function (Blueprint $table): void {
            $table->dropColumn(['photo_path', 'cover_path']);
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        // Колонки возвращаются пустыми: файлы остаются в media library,
        // и восстанавливать пути в обе стороны — работа ради работы.
        Schema::table('masters', function (Blueprint $table): void {
            $table->string('photo_path')->nullable()->after('bio');
            $table->string('cover_path')->nullable()->after('photo_path');
        });

        Schema::table('events', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('description');
        });
    }

    /**
     * @param  class-string<Master|Event>  $model
     */
    private function move(string $table, string $column, string $model, string $collection): void
    {
        $rows = DB::table($table)->whereNotNull($column)->pluck($column, 'id');

        foreach ($rows as $id => $path) {
            // Файл мог исчезнуть с диска мимо приложения — тогда переносить
            // нечего, а запись всё равно должна пережить миграцию.
            if (! Storage::disk('public')->exists($path)) {
                continue;
            }

            // Файл переезжает, а не копируется: старая раскладка на диске
            // после этого не нужна.
            $model::find($id)?->addMediaFromDisk($path, 'public')->toMediaCollection($collection);
        }
    }
};
