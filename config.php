<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\api\BaseController;
use humhub\components\Application;

return [
    'id' => 'rest',
    'class' => 'humhub\modules\rest\Module',
    'namespace' => 'humhub\modules\rest',
    'events' => [
        [Application::class, Application::EVENT_BEFORE_REQUEST, ['\humhub\modules\rest\Events', 'onBeforeRequest']],
        // Token authentication for the platform's own API controllers (`/api/v2`), see
        // Events::onCollectApiAuthMethods()
        [BaseController::class, BaseController::EVENT_COLLECT_AUTH_METHODS, ['\humhub\modules\rest\Events', 'onCollectApiAuthMethods']],
        ['humhub\modules\legal\services\ExportService', 'collectUserData', ['humhub\modules\rest\Events', 'onLegalModuleUserDataExport']],
    ],
];
