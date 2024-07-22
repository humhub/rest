<?php

namespace humhub\modules\rest\models;

use humhub\modules\space\models\Space;
use humhub\modules\user\models\User;

/**
 * @property User|null $originator
 * @property User|null $createdBy
 * @property User|null $updatedBy
 * @property Space|null $space
 */
class Invite extends \humhub\modules\user\models\Invite
{
    public function getCreatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'created_by']);
    }

    public function getUpdatedBy()
    {
        return $this->hasOne(User::class, ['id' => 'updated_by']);
    }
}
