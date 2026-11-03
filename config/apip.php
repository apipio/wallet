<?php

return [
    // 网关地址
    'uri' => env('APIP_URL', 'https://apip.io/api/'),

    // 应用 ID
    'app_id' => env('APIP_APP_ID'),

    // 应用密钥, 禁止提交到版本库或写入日志
    'app_key' => env('APIP_APP_KEY'),
];
