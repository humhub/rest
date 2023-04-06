<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use yii\test\ActiveFixture;

class SettingFixture extends ActiveFixture
{
    public $modelClass = 'humhub\models\Setting';

    public $dataFile = '@rest/tests/codeception/fixtures/data/setting.php';
}
