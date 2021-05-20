<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2021 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\tests\codeception\fixtures;

use humhub\modules\comment\models\Comment;
use yii\test\ActiveFixture;

class CommentFixture extends ActiveFixture
{

    public $modelClass = Comment::class;
    public $dataFile = '@rest/tests/codeception/fixtures/data/comment.php';

}
