<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\space\models\Space;
use yii\helpers\Url;


/**
 * Class SpaceDefinitions
 */
class SpaceDefinitions
{

    public static function getSpaceShort(Space $space)
    {
        return [
            'id' => $space->id,
            'guid' => $space->guid,
            'name' => $space->name,
            'description' => $space->description,
            'url' => Url::to(['/', 'container' => $space], true)
        ];
    }

    public static function getSpace(Space $space)
    {
        return [
            'id' => $space->id,
            'guid' => $space->guid,
            'name' => $space->name,
            'description' => $space->description,
            'url' => Url::to(['/', 'container' => $space], true),
            'visibility' => $space->visibility,
            'join_policy' => $space->join_policy,
            'status' => $space->status,
            'tags' => $space->tags,
            'owner' => UserDefinitions::getUserShort($space->ownerUser),
            'members' => static::getMembers($space),
        ];
    }

    public static function getMembers(Space $space)
    {
        $members = [];
        foreach ($space->getMembershipUser()->all() as $member) {
            $members[] = UserDefinitions::getUserShort($member);
        }
        return $members;
    }

}

