<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\topic\models\Topic;

class TopicDefinitions
{
    public static function getTopic(Topic $topic)
    {
        return [
            'id' => $topic->id,
            'name' => $topic->name,
        ];
    }
}