<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use humhub\modules\like\models\Like;
use yii\test\ActiveFixture;

class LikeFixture extends ActiveFixture
{
    public $modelClass = Like::class;
    public $dataFile = '@rest/tests/codeception/fixtures/data/like.php';

}
