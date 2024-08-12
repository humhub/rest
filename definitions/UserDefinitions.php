<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\components\Event;
use humhub\modules\comment\models\Comment;
use humhub\modules\file\models\File;
use humhub\modules\like\models\Like;
use humhub\modules\post\models\Post;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\User;
use yii\helpers\Url;

/**
 * Class UserDefinitions
 */
class UserDefinitions
{
    public const EVENT_INIT_ALL_USER_DATA = 'initAllUserData';

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

    public static function getAllUserData(User $user): array
    {
        $data = [
            'user' => self::getUser($user),
            'post' => array_map(function ($post) {
                return PostDefinitions::getPost($post);
            }, Post::findAll(['created_by' => $user->id])),
            'comment' => array_map(function ($comment) {
                return CommentDefinitions::getComment($comment);
            }, Comment::findAll(['created_by' => $user->id])),
            'file' => array_map(function ($file) {
                return FileDefinitions::getFile($file);
            }, File::findAll(['created_by' => $user->id])),
            'like' => array_map(function ($like) {
                return LikeDefinitions::getLike($like);
            }, Like::findAll(['created_by' => $user->id])),
        ];

        $event = new Event(['result' => ['user' => $user, 'data' => $data]]);
        Event::trigger(self::class, self::EVENT_INIT_ALL_USER_DATA, $event);

        return $event->result['data'];
    }
}
