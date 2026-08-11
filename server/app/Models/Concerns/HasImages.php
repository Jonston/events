<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Картинки модели через media library.
 *
 * Сверх самой библиотеки здесь только две вещи, нужные всем моделям сразу:
 * приведение пустого адреса к null и общая для формы связка «прислали файл —
 * заменили, попросили убрать — стёрли».
 */
trait HasImages
{
    use InteractsWithMedia;

    /**
     * Адрес картинки или null, если её нет.
     *
     * `getFirstMediaUrl()` на пустой коллекции отдаёт пустую строку — в JSON
     * это выглядит как заданная картинка, и клиент рисует пустой `<img>`
     * вместо запасного рисунка.
     */
    public function mediaUrl(string $collection, string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia($collection);

        if ($media === null) {
            return null;
        }

        // Пока конверсия не сделана, ссылка на неё ведёт в 404 — в этом
        // случае отдаём оригинал: он всего лишь тяжелее.
        return $conversion !== '' && $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }

    /**
     * Применяет к коллекции то, что пришло из формы.
     *
     * Форма всегда говорит об одном файле в двух видах — новый файл или
     * галочка «убрать», — и разводить это по месту вызова значит повторять
     * одно и то же условие в каждом контроллере.
     */
    public function syncImage(string $collection, ?UploadedFile $file, bool $remove = false): void
    {
        if ($file !== null) {
            // Коллекция объявлена singleFile: прежний файл библиотека
            // удалит сама, отдельной уборки не требуется.
            $this->addMedia($file)->toMediaCollection($collection);

            return;
        }

        if ($remove) {
            $this->clearMediaCollection($collection);
        }
    }
}
