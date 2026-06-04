<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use humhub\models\RecordMap;
use yii\test\ActiveFixture;

class RecordMapFixture extends ActiveFixture
{
    public $modelClass = RecordMap::class;
    public $dataFile = '@rest/tests/codeception/fixtures/data/record-map.php';
}
