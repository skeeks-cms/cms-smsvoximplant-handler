<?php
return [
    'components' => [
        'cms' => [
            'smsHandlers'             => [
                'smsru' => [
                    'class' => \skeeks\cms\sms\smsvoximplant\SmsvoximplantHandler::class
                ]
            ]
        ],
    ],
];