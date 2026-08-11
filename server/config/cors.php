<?php

declare(strict_types=1);

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    /*
     * Перечислены поимённо, а не через `*`: клиент шлёт запросы с
     * `credentials: 'include'`, а браузер отклоняет предварительный запрос,
     * если в ответ на такой пришла звёздочка вместо конкретного адреса.
     */
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', (string) env('FRONTEND_URLS', 'http://localhost:3010')))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 60 * 60,

    'supports_credentials' => true,

];
