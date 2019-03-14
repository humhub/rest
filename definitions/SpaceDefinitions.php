<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

/**
 * Class SpaceDefinitions
 *
 * @package humhub\modules\rest\definitions
 */

class SpaceDefinitions
{
    public static function getSpace($space)
    {
        $data = [
            'id' => $space->id,
            'name' => $space->name,
            'description' => $space->description,
            'tags' => $space -> tags,
            'created_at' => $space -> created_at,
            'updated_at' => $space -> updated_at,
            'created_by' => UserDefinitions::getUserShort($space->createdBy),
            'default_content_visibility' => $space -> default_content_visibility,
            'url' => $space -> url,
            'join_policy' => $space -> join_policy,
            'visibility' => $space -> visibility,
            'status' => $space -> status,
            'contentcontainer_id' => $space -> contentcontainer_id
        ];
        return $data;
    }
}