<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use humhub\modules\topic\models\Topic;
use yii\test\ActiveFixture;

class TopicFixture extends ActiveFixture
{

    public $modelClass = Topic::class;
    public $dataFile = '@rest/tests/codeception/fixtures/data/topic.php';

}
