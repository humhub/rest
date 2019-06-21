<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\components\ActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\like\models\Like;

/**
 * Class CommentDefinitions
 * @package humhub\modules\rest\definitions
 */
class LikeDefinitions
{

    public static function getLikesSummary(ActiveRecord $record)
    {
        $result = [];

        $model = get_class($record);
        $pk = $record->getPrimaryKey();
        if ($record instanceof Content) {
            $model = $record->object_model;
            $pk = $record->object_id;
        }

        $result['total'] = count(Like::GetLikes($model, $pk));
        return $result;
    }

    public static function getLike(Like $like)
    {
        return [
            'id' => $like->id,
            'createdBy' => UserDefinitions::getUserShort($like->user),
            'createdAt' => $like->created_at
        ];
    }

}