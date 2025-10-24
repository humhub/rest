<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\comment\models\Comment;
use humhub\modules\like\models\Like;
use humhub\modules\notification\models\Notification;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;

class SourceDefinitions
{
    public static function getSource($source)
    {
        return match (true) {
            $source instanceof Space => SpaceDefinitions::getSpaceShort($source),
            $source instanceof Post => PostDefinitions::getPost($source),
            $source instanceof Comment => CommentDefinitions::getComment($source),
            $source instanceof Like => LikeDefinitions::getLike($source),
            default => $source::class . ' definitions are not yet supported.',
        };
    }
}
