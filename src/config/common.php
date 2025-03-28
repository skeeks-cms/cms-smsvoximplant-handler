<?php
return [
    'components' => [
        'cms' => [
            'smsHandlers'             => [
                'smsvoximplantphp' => [
                    'class' => \skeeks\cms\sms\smsvoximplant\SmsvoximplantHandler::class
                ]
            ]
        ],
    ],
];