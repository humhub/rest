<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest;

use humhub\components\Event;
use humhub\modules\activity\models\Activity;
use humhub\modules\comment\models\Comment;
use humhub\modules\file\models\File;
use humhub\modules\friendship\models\Friendship;
use humhub\modules\legal\events\UserDataCollectionEvent;
use humhub\modules\like\models\Like;
use humhub\modules\notification\models\Notification;
use humhub\modules\post\models\Post;
use humhub\modules\rest\definitions\ActivityDefinitions;
use humhub\modules\rest\definitions\CommentDefinitions;
use humhub\modules\rest\definitions\FileDefinitions;
use humhub\modules\rest\definitions\InviteDefinitions;
use humhub\modules\rest\definitions\LikeDefinitions;
use humhub\modules\rest\definitions\NotificationDefinitions;
use humhub\modules\rest\definitions\PostDefinitions;
use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\rest\models\Invite;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\user\models\Auth;
use humhub\modules\user\models\Follow;
use humhub\modules\user\models\Group;
use humhub\modules\user\models\Mentioning;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Session;
use Yii;

class Events
{
    public static function onBeforeRequest($event)
    {

        // Only prepare if API request
        if (substr(Yii::$app->request->pathInfo, 0, 4) != 'api/') {
            return;
        }

        // JSON for all API requests except the API configuration
        if (substr(Yii::$app->request->pathInfo, 0, 9) != 'rest/admin') {
            Yii::$app->response->format = 'json';
        }

        /* @var Module $module */
        $module = Yii::$app->getModule('rest');
        $module->addRules([

            // Auth
            ['pattern' => 'auth/login', 'route' => 'rest/auth/auth/index', 'verb' => ['POST']],
            ['pattern' => 'auth/impersonate', 'route' => 'rest/auth/auth/impersonate', 'verb' => ['POST']],
            ['pattern' => 'auth/current', 'route' => 'rest/auth/auth/current', 'verb' => ['GET', 'HEAD']],

            // User: Default Controller
            ['pattern' => 'user/', 'route' => 'rest/user/user/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/get-by-username', 'route' => 'rest/user/user/get-by-username', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/get-by-email', 'route' => 'rest/user/user/get-by-email', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/get-by-authclient', 'route' => 'rest/user/user/get-by-authclient', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/<id:\d+>', 'route' => 'rest/user/user/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/<id:\d+>', 'route' => 'rest/user/user/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'user/<id:\d+>', 'route' => 'rest/user/user/delete', 'verb' => ['DELETE']],
            ['pattern' => 'user/full/<id:\d+>', 'route' => 'rest/user/user/hard-delete', 'verb' => ['DELETE']],
            ['pattern' => 'user/', 'route' => 'rest/user/user/create', 'verb' => 'POST'],
            ['pattern' => 'user/<id:\d+>/auth-client', 'route' => 'rest/user/user/add-auth-client', 'verb' => 'POST'],

            // User: Group Controller
            ['pattern' => 'user/group', 'route' => 'rest/user/group/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/group', 'route' => 'rest/user/group/create', 'verb' => 'POST'],
            ['pattern' => 'user/group/<id:\d+>', 'route' => 'rest/user/group/view', 'verb' => ['GET']],
            ['pattern' => 'user/group/<id:\d+>', 'route' => 'rest/user/group/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'user/group/<id:\d+>', 'route' => 'rest/user/group/delete', 'verb' => ['DELETE']],
            ['pattern' => 'user/group/<id:\d+>/member', 'route' => 'rest/user/group/members', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/group/<id:\d+>/member', 'route' => 'rest/user/group/member-add', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'user/group/<id:\d+>/member', 'route' => 'rest/user/group/member-remove', 'verb' => ['DELETE']],

            // User: Invite Controller
            ['pattern' => 'user/invite', 'route' => 'rest/user/invite/index', 'verb' => 'POST'],
            ['pattern' => 'user/invite', 'route' => 'rest/user/invite/list', 'verb' => 'GET'],
            ['pattern' => 'user/invite/<id:\d+>', 'route' => 'rest/user/invite/cancel', 'verb' => 'DELETE'],
            ['pattern' => 'user/invite/<id:\d+>', 'route' => 'rest/user/invite/resend', 'verb' => 'PATCH'],

            // User: Session Controller
            ['pattern' => 'user/session/all/<id:\d+>', 'route' => 'rest/user/session/delete-from-user', 'verb' => 'DELETE'],

            // Space: Default Controller
            ['pattern' => 'space/', 'route' => '/rest/space/space/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'space/<id:\d+>', 'route' => '/rest/space/space/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'space/', 'route' => '/rest/space/space/create', 'verb' => 'POST'],
            ['pattern' => 'space/<id:\d+>', 'route' => '/rest/space/space/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'space/<id:\d+>', 'route' => '/rest/space/space/delete', 'verb' => 'DELETE'],

            // Space: Archive Controller
            ['pattern' => 'space/<id:\d+>/archive', 'route' => '/rest/space/archive/archive', 'verb' => 'PATCH'],
            ['pattern' => 'space/<id:\d+>/unarchive', 'route' => '/rest/space/archive/unarchive', 'verb' => 'PATCH'],

            // Space: Membership Controller
            ['pattern' => 'space/<spaceId:\d+>/membership', 'route' => '/rest/space/membership/index', 'verb' => 'GET'],
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>', 'route' => '/rest/space/membership/create', 'verb' => 'POST'],
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>/role', 'route' => '/rest/space/membership/role', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'space/<spaceId:\d+>/membership/<userId:\d+>', 'route' => '/rest/space/membership/delete', 'verb' => 'DELETE'],

            // Content
            ['pattern' => 'content/find-by-container/<id:\d+>', 'route' => 'rest/content/content/find-by-container', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'content/container', 'route' => 'rest/content/container/list', 'verb' => 'GET'],
            ['pattern' => 'content/<id:\d+>', 'route' => 'rest/content/content/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'content/<id:\d+>', 'route' => 'rest/content/content/delete', 'verb' => 'DELETE'],
            //['pattern' => 'content/pin/<id:\d+>', 'route' => 'api/user/content/pin', 'verb' => 'POST'],
            //['pattern' => 'content/unpin/<id:\d+>', 'route' => 'api/user/content/unpin', 'verb' => 'POST'],
            //['pattern' => 'content/archive/<id:\d+>', 'route' => 'api/user/content/archive', 'verb' => 'POST'],
            //['pattern' => 'content/unarchive/<id:\d+>', 'route' => 'api/user/content/unarchive', 'verb' => 'POST'],

            // Comment
            ['pattern' => 'comment', 'route' => 'rest/comment/comment/create', 'verb' => 'POST'],
            ['pattern' => 'comment/<id:\d+>', 'route' => 'rest/comment/comment/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'comment/<id:\d+>', 'route' => 'rest/comment/comment/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'comment/<id:\d+>', 'route' => 'rest/comment/comment/delete', 'verb' => 'DELETE'],
            ['pattern' => 'comment/find-by-object', 'route' => 'rest/comment/comment/find-by-object', 'verb' => 'GET'],
            ['pattern' => 'comment/content/<id:\d+>', 'route' => 'rest/comment/comment/find-by-content', 'verb' => 'GET'],

            // Like
            ['pattern' => 'like/<id:\d+>', 'route' => 'rest/like/like/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'like/<id:\d+>', 'route' => 'rest/like/like/delete', 'verb' => 'DELETE'],
            ['pattern' => 'like/find-by-object', 'route' => 'rest/like/like/find-by-object', 'verb' => 'GET'],

            // Post
            ['pattern' => 'post/', 'route' => 'rest/post/post/find', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'post/<id:\d+>', 'route' => 'rest/post/post/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'post/<id:\d+>', 'route' => 'rest/post/post/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'post/<id:\d+>', 'route' => 'rest/post/post/delete', 'verb' => ['DELETE']],
            ['pattern' => 'post/<id:\d+>/upload-files', 'route' => 'rest/post/post/attach-files', 'verb' => 'POST'],
            ['pattern' => 'post/container/<containerId:\d+>', 'route' => 'rest/post/post/create', 'verb' => 'POST'],
            ['pattern' => 'post/container/<containerId:\d+>', 'route' => 'rest/post/post/find-by-container', 'verb' => 'GET'],

            // Topic
            ['pattern' => 'topic/', 'route' => 'rest/topic/topic/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'topic/<id:\d+>', 'route' => 'rest/topic/topic/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'topic/<id:\d+>', 'route' => 'rest/topic/topic/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'topic/<id:\d+>', 'route' => 'rest/topic/topic/delete', 'verb' => ['DELETE']],
            ['pattern' => 'topic/container/<containerId:\d+>', 'route' => 'rest/topic/topic/create', 'verb' => 'POST'],
            ['pattern' => 'topic/container/<containerId:\d+>', 'route' => 'rest/topic/topic/find-by-container', 'verb' => 'GET'],

            // Activity
            ['pattern' => 'activity/', 'route' => 'rest/activity/activity/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'activity/<id:\d+>', 'route' => 'rest/activity/activity/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'activity/container/<containerId:\d+>', 'route' => 'rest/activity/activity/find-by-container', 'verb' => ['GET', 'HEAD']],

            // Notification
            ['pattern' => 'notification/', 'route' => 'rest/notification/notification/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'notification/unseen/', 'route' => 'rest/notification/notification/unseen', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'notification/mark-as-seen/', 'route' => 'rest/notification/notification/mark-as-seen', 'verb' => ['PATCH']],
            ['pattern' => 'notification/<id:\d+>', 'route' => 'rest/notification/notification/view', 'verb' => ['GET', 'HEAD']],

            // File
            ['pattern' => 'file/download/<id:\d+>', 'route' => 'rest/file/file/download', 'verb' => ['GET', 'HEAD']],

        ]);

        Yii::$app->urlManager->addRules([

            // API Config
            ['pattern' => 'rest/admin/index', 'route' => 'rest/admin', 'verb' => ['POST', 'GET']],

            // Catch all to ensure verbs
            ['pattern' => 'rest/<tmpParam:.*>', 'route' => 'rest/error/notfound'],

        ], true);

        Event::trigger(Module::class, Module::EVENT_REST_API_ADD_RULES);
    }

    private static function addModuleNotFoundRoutes($moduleId)
    {
        /* @var Module $module */
        $module = Yii::$app->getModule('rest');
        $module->addRules([
            ['pattern' => $moduleId, 'route' => "rest/{$moduleId}/{$moduleId}/not-supported"],
            ['pattern' => "{$moduleId}/<tmpParam:.*>", 'route' => "rest/{$moduleId}/{$moduleId}/not-supported"],
        ]);
    }

    public static function onLegalModuleUserDataExport(UserDataCollectionEvent $event)
    {
        $event->addExportData('user', UserDefinitions::getUser($event->user));

        $event->addExportData('password', array_map(function ($password) {
            return UserDefinitions::getPassword($password);
        }, Password::findAll(['user_id' => $event->user->id])));

        $event->addExportData('friendship', array_map(function ($friendship) {
            return UserDefinitions::getFriendship($friendship);
        }, Friendship::findAll(['user_id' => $event->user->id])));

        $event->addExportData('mentioning', array_map(function ($mentioning) {
            return UserDefinitions::getMentioning($mentioning);
        }, Mentioning::findAll(['user_id' => $event->user->id])));

        $event->addExportData('user-follow', array_map(function ($follow) {
            return UserDefinitions::getUserFollow($follow);
        }, Follow::findAll(['user_id' => $event->user->id])));

        $event->addExportData('auth', array_map(function ($auth) {
            return UserDefinitions::getUserAuth($auth);
        }, Auth::findAll(['user_id' => $event->user->id])));

        $event->addExportData('session', array_map(function ($session) {
            return UserDefinitions::getUserHttpSession($session);
        }, Session::findAll(['user_id' => $event->user->id])));

        $event->addExportData('group', array_map(function ($group) {
            return UserDefinitions::getGroup($group);
        }, Group::find()
            ->innerJoin('group_user', 'group_user.group_id = group.id')
            ->where(['group_user.user_id' => $event->user->id])
            ->all()));

        $event->addExportData('post', array_map(function ($post) {
            return PostDefinitions::getPost($post);
        }, Post::findAll(['created_by' => $event->user->id])));

        $event->addExportData('comment', array_map(function ($comment) {
            return CommentDefinitions::getComment($comment);
        }, Comment::findAll(['created_by' => $event->user->id])));

        $event->addExportData('like', array_map(function ($like) {
            return LikeDefinitions::getLike($like);
        }, Like::findAll(['created_by' => $event->user->id])));

        $event->addExportData('activity', array_map(function ($activity) {
            return ActivityDefinitions::getActivity($activity);
        }, Activity::find()
            ->innerJoin('content', 'activity.id = content.object_id and content.object_model = :activityClass', ['activityClass' => Activity::class])
            ->where(['created_by' => $event->user->id])
            ->all()));

        $event->addExportData('invite', array_map(function ($invite) {
            return InviteDefinitions::getInvite($invite);
        }, Invite::findAll(['created_by' => $event->user->id])));

        $event->addExportData('notification', array_map(function ($notification) {
            return NotificationDefinitions::getNotification($notification);
        }, Notification::findAll(['user_id' => $event->user->id])));

        $event->addExportData('space', array_map(function ($space) {
            return SpaceDefinitions::getSpace($space);
        }, Space::findAll(['created_by' => $event->user->id])));

        $event->addExportData('space-membership', array_map(function ($membership) {
            return SpaceDefinitions::getSpaceMembership($membership);
        }, Membership::findAll(['user_id' => $event->user->id])));

        $files = File::findAll(['created_by' => $event->user->id]);
        $event->addExportData('file', array_map(function ($file) {
            return FileDefinitions::getFile($file);
        }, $files));

        foreach ($files as $file) {
            $event->addExportFile($file->file_name, $file->store->get());
        }
    }
}
