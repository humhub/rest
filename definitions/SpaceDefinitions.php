<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\space\models\Membership;
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
            'url' => Url::to(['/', 'container' => $space], true),
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
            'contentcontainer_id' => $space->contentcontainer_id,
            'visibility' => $space->visibility,
            'join_policy' => $space->join_policy,
            'status' => $space->status,
            'tags' => $space->tags,
            'owner' => UserDefinitions::getUserShort($space->ownerUser),
            'hideMembers' => (int)$space->getAdvancedSettings()->hideMembers,
            'hideAbout' => (int)$space->getAdvancedSettings()->hideAbout,
            'hideActivities' => (int)$space->getAdvancedSettings()->hideActivities,
            'hideFollowers' => (int)$space->getAdvancedSettings()->hideFollowers,
            'indexUrl' => (string)$space->getAdvancedSettings()->indexUrl,
            'indexGuestUrl' => (string)$space->getAdvancedSettings()->indexGuestUrl,
        ];
    }

    public static function getSpaceMembership(Membership $membership)
    {
        return [
            'user' => UserDefinitions::getUserShort($membership->user),
            'role' => $membership->group_id,
            'status' => $membership->status,
            'can_cancel_membership' => $membership->can_cancel_membership,
            'send_notifications' => $membership->send_notifications,
            'show_at_dashboard' => $membership->show_at_dashboard,
            'originator_user' => ($membership->originator !== null) ? UserDefinitions::getUserShort($membership->originator) : null,
            'member_since' => $membership->created_at,
            'request_message' => $membership->request_message,
            'updated_at' => $membership->updated_at,
            'last_visit' => $membership->last_visit,
        ];
    }

}
