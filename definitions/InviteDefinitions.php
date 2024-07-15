<?php

namespace humhub\modules\rest\definitions;

use humhub\modules\user\models\Invite;

class InviteDefinitions
{
    public static function getInvite(Invite $invite)
    {
        return [
            'id' => $invite->id,
            'email' => $invite->email,
        ];
    }
}
