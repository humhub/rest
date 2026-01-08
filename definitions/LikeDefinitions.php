<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\content\interfaces\ContentProvider;
use humhub\modules\like\models\Like;
use humhub\modules\like\services\LikeService;

/**
 * Class CommentDefinitions
 * @package humhub\modules\rest\definitions
 */
class LikeDefinitions
{
    public static function getLikesSummary(ContentProvider $record)
    {
        $result = [];
        $result['total'] = (new LikeService($record))->getCount();
        return $result;
    }

    public static function getLike(Like $like)
    {
        return [
            'id' => $like->id,
            'createdBy' => UserDefinitions::getUserShort($like->createdBy),
            'createdAt' => $like->created_at,
        ];
    }

}
