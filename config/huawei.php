<?php

return [
    'loginUrl' => env(
        'HUAWEI_BASE_URL',
        'https://oauth-login.cloud.huawei.com'
    ),
    'pushApiUrl' => env('HUAWEI_PUSH_URL', 'https://push-api.cloud.huawei.com'),
    'huawei-app-id' => [
        'sgocGpLite' => env('HUAWEI_SGOC_GP_LITE_ID', 102804755),
        'sgocGuardTalk' => env('HUAWEI_SGOC_GUARD_TALK_ID', 102551777),
        'pmocPMTalk' => env('HUAWEI_PMOC_PM_TALK_ID', 105023999),
        'sgocGuardPanel' => env('HUAWEI_GUARD_PANEL_ID', 102804621),
        'mymooban' => env('HUAWEI_MYMOOBAN_ID', 102527955),
    ],
    'huawei-client-secret' => [
        'sgocGpLite' => env(
            'HUAWEI_SGOC_GP_LITE_CLIENT_SECRET',
            '9b93bb25d3a9f1a7b82aa90ee62549364d2bcd759496ac39bca266018c6ab1c9'
        ),
        'sgocGuardTalk' => env(
            'HUAWEI_SGOC_GUARD_TALK_SECRET',
            '5126902e92084833b4b3be06668aba1b78b24b22f54b5ff8a8e3c9000db702a6'
        ),
        'pmocPMTalk' => env(
            'HUAWEI_PMOC_PM_TALK_SECRET',
            '5c5405a65bc554d51cfbed88fca922ddf3a26cb9de7ca9458b9c9a4c802cebfd'
        ),
        'sgocGuardPanel' => env(
            'HUAWEI_GUARD_PANEL_SECRET',
            '0aacc0f17692eb09dee3a7e7d173778d37ae16a694fb9a592fba492ee78c9bd0'
        ),
        'mymooban' => env(
            'HUAWEI_MYMOOBAN_SECRET',
            'e5271e066bb81a256a6c5d19e4a13cd0d02e91b5885aa7e19d5a3b03321abde9'
        ),
    ],
];
