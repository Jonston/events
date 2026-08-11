<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Убирает CR из уже сохранённых текстов.
 *
 * Форма с картинками уходит как multipart, а он переводит строки в CR+LF;
 * при разборе страницы браузер возвращает их без CR — и разметка от сервера
 * расходится с клиентской на каждом абзаце. Новые записи чинит запрос,
 * а старым нужен этот проход.
 */
return new class extends Migration
{
    /** Таблица и её многострочные колонки. */
    private const TEXT = [
        'masters' => ['bio'],
        'events' => ['summary', 'description'],
        'leads' => ['message'],
        'reviews' => ['body'],
    ];

    public function up(): void
    {
        foreach (self::TEXT as $table => $columns) {
            foreach ($columns as $column) {
                DB::table($table)
                    ->whereNotNull($column)
                    ->update([$column => DB::raw("REPLACE(`{$column}`, '\r\n', '\n')")]);
            }
        }
    }

    public function down(): void
    {
        // Обратно возвращать нечего: CR в тексте никому не нужен.
    }
};
