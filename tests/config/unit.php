<?php

/**
 * Here you can overwrite your unit humhub config. The default config resides in @humhubTests/codeception/config/config.php
 */
return [
    'components' => [
        'urlManager' => [
            // The module requires pretty URLs, see requirements.php
            'enablePrettyUrl' => true,
        ],
    ],
];
