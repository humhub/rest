<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest;

use humhub\components\Event;
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
            ['pattern' => 'auth/login/', 'route' => 'rest/auth/auth/index', 'verb' => ['POST']],
            ['pattern' => 'auth/current', 'route' => 'rest/auth/auth/current', 'verb' => ['GET', 'HEAD']],

            // User: Default Controller
            ['pattern' => 'user/', 'route' => 'rest/user/user/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/get-by-username', 'route' => 'rest/user/user/get-by-username', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/get-by-email', 'route' => 'rest/user/user/get-by-email', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/<id:\d+>', 'route' => 'rest/user/user/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'user/<id:\d+>', 'route' => 'rest/user/user/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'user/<id:\d+>', 'route' => 'rest/user/user/delete', 'verb' => ['DELETE']],
            ['pattern' => 'user/full/<id:\d+>', 'route' => 'rest/user/user/hard-delete', 'verb' => ['DELETE']],
            ['pattern' => 'user/', 'route' => 'rest/user/user/create', 'verb' => 'POST'],

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
            //['pattern' => 'user/invite', 'route' => 'api/user/invite/index', 'verb' => 'POST'],

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
            ['pattern' => 'comment/<id:\d+>', 'route' => 'rest/comment/comment/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'comment/<id:\d+>', 'route' => 'rest/comment/comment/delete', 'verb' => 'DELETE'],

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
            ['pattern' => 'rest/<tmpParam:.*>', 'route' => 'rest/error/notfound']

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
}
