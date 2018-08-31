<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use yii\helpers\Url;


/**
 * Class AccountController
 */
class UserDefinitions
{

    public static function getUserShort(User $user)
    {
        return [
            'id' => $user->id,
            'guid' => $user->guid,
            'display_name' => $user->displayName,
            'url' => Url::to(['/', 'container' => $user], true)
        ];
    }

    public static function getUser(User $user)
    {
        return [
            'id' => $user->id,
            'guid' => $user->guid,
            'display_name' => $user->displayName,
            'url' => Url::to(['/', 'container' => $user], true),
            'account' => static::getAccount($user),
            'profile' => static::getProfile($user->profile)
        ];
    }

    public static function getProfile(Profile $profile)
    {
        $attributes = $profile->attributes;
        unset($attributes['user_id']);
        return $attributes;
    }

    public static function getAccount(User $user)
    {
        return [
            'id' => $user->id,
            'guid' => $user->guid,
            'username' => $user->username,
            'email' => $user->email,
            'visibility' => $user->visibility,
            'status' => $user->status,
            'tags' => $user->tags,
            'language' => $user->language,
            'time_zone' => $user->time_zone,
            'contentcontainer_id' => $user->contentcontainer_id,
        ];
    }

}

