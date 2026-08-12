<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * История статусов заявки.
 *
 * Статус — единственное, что мастер в заявке меняет, и по нему считаются
 * занятые места. Одно текущее значение не отвечает на вопрос «кто и когда
 * снял бронь»; за этим и нужна история с комментарием.
 */
return new class extends Migration
{
    /**
     * Старые состояния, которых больше нет, и их замена.
     *
     * «В работе» — это просто необработанная заявка. «Состоялось» —
     * подтверждённая бронь. «Отказ» ближе всего к возврату: человек
     * передумал, место освободилось.
     */
    private const REPLACEMENTS = [
        'in_progress' => 'new',
        'completed' => 'booked',
        'rejected' => 'refunded',
    ];

    public function up(): void
    {
        Schema::create('lead_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            // Кто перевёл. Null — запись из миграции или автоматика:
            // человек мог и не участвовать.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'id']);
        });

        foreach (self::REPLACEMENTS as $old => $new) {
            DB::table('leads')->where('status', $old)->update(['status' => $new]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_changes');
    }
};
