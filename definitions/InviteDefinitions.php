<?php

namespace humhub\modules\rest\definitions;

use humhub\modules\rest\models\Invite;
use yii\helpers\Url;

class InviteDefinitions
{
    public static function getInvite(Invite $invite)
    {
        return [
            'id' => $invite->id,
            'email' => $invite->email,
            'firstname' => $invite->firstname,
            'lastname' => $invite->lastname,
            'language' => $invite->language,
            'space' => $invite->space ? SpaceDefinitions::getSpaceShort($invite->space) : null,
            'invitationUrl' => Url::to(['/user/registration', 'token' => $invite->token], true),
            'originator' => $invite->originator ? UserDefinitions::getUserShort($invite->originator) : null,
            'createdBy' => $invite->createdBy ? UserDefinitions::getUserShort($invite->createdBy) : null,
            'updatedBy' => $invite->updatedBy ? UserDefinitions::getUserShort($invite->updatedBy) : null,
            'createdAt' => $invite->created_at,
            'updatedAt' => $invite->updated_at,
        ];
    }
}
