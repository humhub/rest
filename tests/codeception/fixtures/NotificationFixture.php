<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use humhub\modules\notification\models\Notification;
use yii\test\ActiveFixture;

class NotificationFixture extends ActiveFixture
{
    public $modelClass = Notification::class;
    public $dataFile = '@rest/tests/codeception/fixtures/data/notification.php';

}
