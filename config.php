<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\Application;
use humhub\modules\admin\widgets\AdminMenu;

/** @noinspection MissedFieldInspection **/
return [
    'id' => 'rest',
    'class' => 'humhub\modules\rest\Module',
    'namespace' => 'humhub\modules\rest',
    'events' => [
        [AdminMenu::class, AdminMenu::EVENT_INIT, ['\humhub\modules\rest\Events', 'onAdminMenuInit']],
        [Application::class, Application::EVENT_BEFORE_REQUEST, ['\humhub\modules\rest\Events', 'onBeforeRequest']]
    ]
];
?>
