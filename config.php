<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\Application;

/** @noinspection MissedFieldInspection */
return [
    'id' => 'rest',
    'class' => 'humhub\modules\rest\Module',
    'namespace' => 'humhub\modules\rest',
    'events' => [
        [Application::class, Application::EVENT_BEFORE_REQUEST, ['\humhub\modules\rest\Events', 'onBeforeRequest']]
    ]
];
?>