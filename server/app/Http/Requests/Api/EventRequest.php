<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use App\Http\Requests\Concerns\TidiesInput;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class EventRequest extends FormRequest
{
    use TidiesInput;

    /** Формат, в котором браузерный `datetime-local` отдаёт значение. */
    private const DATE_TIME_FORMAT = 'Y-m-d\TH:i';

    protected function prepareForValidation(): void
    {
        $this->castFlags('isPublished', 'removeImage');
        $this->normalizeNewlines('summary', 'description');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'location' => ['nullable', 'string', 'max:255'],

            // Половина точки бессмысленна: по одной широте карта ничего
            // не покажет, поэтому координаты приходят только парой.
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],

            'startsAt' => ['required', 'date_format:'.self::DATE_TIME_FORMAT],
            'endsAt' => ['nullable', 'date_format:'.self::DATE_TIME_FORMAT, 'after:startsAt'],

            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            // Доля, а не сумма: цена меняется чаще, чем условия оплаты,
            // и пересчитывать предоплату руками при каждой правке — лишний повод
            // ошибиться.
            'depositRate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'isPublished' => ['boolean'],

            'image' => ['nullable', 'image', 'max:5120'],
            'removeImage' => ['boolean'],

            // Галерея приходит целиком и в нужном порядке. Снимок, который
            // уже лежит на сервере, обозначается одним id: перезаливать его
            // ради перестановки было бы расточительно.
            'gallery' => ['array', 'max:60'],
            'gallery.*.id' => ['nullable', 'integer'],
            'gallery.*.file' => ['nullable', 'image', 'max:8192'],

            // Программа — возможность, а не обязанность: у часовой встречи
            // её нет, у выездной программы это главное содержание страницы.
            'sessions' => ['array', 'max:200'],
            'sessions.*.day' => ['required', 'date_format:Y-m-d'],
            'sessions.*.startsAt' => ['required', 'date_format:H:i'],
            'sessions.*.endsAt' => ['nullable', 'date_format:H:i', 'after:sessions.*.startsAt'],
            'sessions.*.title' => ['required', 'string', 'max:255'],
            'sessions.*.note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Галерея парами «что оставить» и «что загрузить», в том порядке,
     * в каком снимки лягут на страницу.
     *
     * Список берётся из `all()`, а не из `input()`: файлы лежат в своём
     * наборе, и у только что выбранного снимка других полей нет — по одному
     * `input()` он был бы невидим, а галерея молча приезжала бы пустой.
     *
     * @return array<int, array{id: int|null, file: UploadedFile|null}>
     */
    public function gallery(): array
    {
        return collect($this->all()['gallery'] ?? [])
            ->values()
            ->map(fn (mixed $item, int $position): array => [
                'id' => filled($item['id'] ?? null) ? (int) $item['id'] : null,
                'file' => $this->file("gallery.{$position}.file"),
            ])
            ->all();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach ($this->gallery() as $position => $item) {
                    // Ни id, ни файла: снимок потерялся по дороге. Промолчать
                    // здесь значит тихо выбросить его из галереи.
                    if ($item['id'] === null && $item['file'] === null) {
                        $validator->errors()->add(
                            "gallery.{$position}.file",
                            'Не удалось прочитать снимок — выберите его заново.',
                        );
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'название',
            'startsAt' => 'дата начала',
            'endsAt' => 'дата окончания',
            'price' => 'стоимость',
            'capacity' => 'количество мест',
            'sessions.*.day' => 'день программы',
            'sessions.*.startsAt' => 'время начала пункта',
            'sessions.*.endsAt' => 'время окончания пункта',
            'sessions.*.title' => 'название пункта',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'endsAt.after' => 'Мероприятие не может закончиться раньше, чем началось.',
            'sessions.*.endsAt.after' => 'Пункт программы не может закончиться раньше, чем начался.',
        ];
    }
}
