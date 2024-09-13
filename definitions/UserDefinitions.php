<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\friendship\models\Friendship;
use humhub\modules\user\models\Auth;
use humhub\modules\user\models\Follow;
use humhub\modules\user\models\Mentioning;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\Session;
use humhub\modules\user\models\User;
use yii\helpers\Url;

/**
 * Class UserDefinitions
 */
class UserDefinitions
{
    public static function getUserShort(User $user)
    {
        return [
            'id' => $user->id,
            'guid' => $user->guid,
            'display_name' => $user->displayName,
            'url' => Url::to(['/', 'container' => $user], true),
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
            'profile' => static::getProfile($user->profile),
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
            'authclient' => $user->auth_mode,
            'authclient_id' => $user->authclient_id,
            'last_login' => $user->last_login,
        ];
    }

    public static function getGroup(Group $group)
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'description' => $group->description,
            'show_at_registration' => $group->show_at_registration,
            'show_at_directory' => $group->show_at_directory,
            'sort_order' => $group->sort_order,
        ];
    }

    public static function getFriendship(Friendship $friendship)
    {
        return [
            'id' => $friendship->id,
            'user_id' => $friendship->user_id,
            'friend_user_id' => $friendship->friend_user_id,
            'created_at' => $friendship->created_at,
        ];
    }

    public static function getPassword(?Password $password)
    {
        if (!$password) {
            return [];
        }

        return [
            'id' => $password->id,
            'user_id' => $password->user_id,
            'algorithm' => $password->algorithm,
            'password' => $password->password,
            'salt' => $password->salt,
            'created_at' => $password->created_at,
        ];
    }

    public static function getMentioning(Mentioning $mentioning)
    {
        return [
            'id' => $mentioning->id,
            'object_model' => $mentioning->object_model,
            'object_id' => $mentioning->object_id,
            'user_id' => $mentioning->user_id,
        ];
    }

    public static function getUserFollow(Follow $follow)
    {
        return [
            'id' => $follow->id,
            'object_model' => $follow->object_model,
            'object_id' => $follow->object_id,
            'user_id' => $follow->user_id,
            'send_notifications' => $follow->send_notifications,
        ];
    }

    public static function getUserAuth(Auth $auth)
    {
        return [
            'id' => $auth->id,
            'user_id' => $auth->user_id,
            'source' => $auth->source,
            'source_id' => $auth->source_id,
        ];
    }

    public static function getUserHttpSession(Session $session)
    {
        return [
            'id' => $session->id,
            'expire' => $session->expire,
            'user_id' => $session->user_id,
            'data' => $session->data,
        ];
    }
}
