<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Телефоны участника отдельной таблицей.
 *
 * Номер у человека не один: рабочий, личный, номер того, кто едет вместе.
 * Держать их колонкой значило бы выбирать за него, по какому звонить,
 * поэтому номеров сколько угодно, а основной — ровно один и помечен.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_phones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 64);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Один и тот же номер дважды у одного человека — опечатка,
            // а не второй телефон.
            $table->unique(['user_id', 'phone']);
        });

        // Тот номер, что уже был у человека, становится основным.
        DB::table('users')->whereNotNull('phone')->orderBy('id')->each(function (object $user): void {
            DB::table('user_phones')->insert([
                'user_id' => $user->id,
                'phone' => $user->phone,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
        });

        DB::table('user_phones')->where('is_primary', true)->orderBy('id')->each(function (object $row): void {
            DB::table('users')->where('id', $row->user_id)->update(['phone' => $row->phone]);
        });

        Schema::dropIfExists('user_phones');
    }
};
