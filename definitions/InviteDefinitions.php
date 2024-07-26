<?php

namespace humhub\modules\rest\definitions;

use humhub\modules\rest\models\Invite;

class InviteDefinitions
{
    /**
     * @param Invite $invite
     * @return array
     */
    public static function getInvite(Invite $invite)
    {
        return [
            'id' => $invite->id,
            'email' => $invite->email,
            'firstname' => $invite->firstname,
            'lastname' => $invite->lastname,
            'language' => $invite->language,
            'space' => $invite->space ? SpaceDefinitions::getSpaceShort($invite->space) : null,
            'originator' => $invite->originator ? UserDefinitions::getUserShort($invite->originator) : null,
            'createdBy' => $invite->createdBy ? UserDefinitions::getUserShort($invite->createdBy) : null,
            'updatedBy' => $invite->updatedBy ? UserDefinitions::getUserShort($invite->updatedBy) : null,
            'createdAt' => $invite->created_at,
            'updatedAt' => $invite->updated_at,
        ];
    }
}
