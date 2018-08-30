<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;
use humhub\modules\post\models\Post;


/**
 * Class PostDefinitions
 *
 * @package humhub\modules\rest\definitions
 */
class PostDefinitions
{
    public static function getPost(Post $post)
    {
        return [
            'id' => $post->id,
            'message' => $post->message,
            'content' => ContentDefinitions::getContent($post->content)
        ];
    }

}