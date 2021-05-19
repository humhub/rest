<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use yii\test\ActiveFixture;

class ActivityFixture extends ActiveFixture
{

    public $modelClass = 'humhub\modules\Activity\models\Activity';
    public $dataFile = '@rest/tests/codeception/fixtures/data/activity.php';

}
