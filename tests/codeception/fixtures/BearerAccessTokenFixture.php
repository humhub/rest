<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use humhub\modules\rest\models\RestUserBearerToken;
use yii\test\ActiveFixture;

class BearerAccessTokenFixture extends ActiveFixture
{
    public $modelClass = RestUserBearerToken::class;

    public $dataFile = '@rest/tests/codeception/fixtures/data/bearer-access-token.php';
}
