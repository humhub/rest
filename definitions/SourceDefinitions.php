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
        switch (true) {
            case $source instanceof Space :
                return SpaceDefinitions::getSpaceShort($source);
            case $source instanceof Post :
                return PostDefinitions::getPost($source);
            case $source instanceof Comment :
                return CommentDefinitions::getComment($source);
            case $source instanceof Like :
                return LikeDefinitions::getLike($source);
        }

        return get_class($source) . ' definitions are not yet supported.';
    }
}