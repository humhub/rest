<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest;

use Yii;
use yii\web\JsonParser;

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

        Yii::$app->urlManager->addRules([

            // Auth
            ['pattern' => 'api/v1/auth/login/', 'route' => 'rest/auth/auth/index', 'verb' => ['POST']],

            // User: Default Controller
            ['pattern' => 'api/v1/user/', 'route' => 'rest/user/user/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/user/<id:\d+>', 'route' => 'rest/user/user/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/user/<id:\d+>', 'route' => 'rest/user/user/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'api/v1/user/<id:\d+>', 'route' => 'rest/user/user/delete', 'verb' => ['DELETE']],
            ['pattern' => 'api/v1/user/full/<id:\d+>', 'route' => 'rest/user/user/hard-delete', 'verb' => ['DELETE']],
            ['pattern' => 'api/v1/user/', 'route' => 'rest/user/user/create', 'verb' => 'POST'],

            // User: Invite Controller
            //['pattern' => 'api/v1/user/invite', 'route' => 'api/user/invite/index', 'verb' => 'POST'],

            // User: Session Controller
            ['pattern' => 'api/v1/user/session/all/<id:\d+>', 'route' => 'rest/user/session/delete-from-user', 'verb' => 'DELETE'],


            // Space: Default Controller
            ['pattern' => 'api/v1/space/', 'route' => '/rest/space/space/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/space/<id:\d+>', 'route' => '/rest/space/space/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/space/', 'route' => '/rest/space/space/create', 'verb' => 'POST'],
            ['pattern' => 'api/v1/space/<id:\d+>', 'route' => '/rest/space/space/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'api/v1/space/<id:\d+>', 'route' => '/rest/space/space/delete', 'verb' => 'DELETE'],

            // Space: Archive Controller
            ['pattern' => 'api/v1/space/<id:\d+>/archive', 'route' => '/rest/space/archive/archive', 'verb' => 'PATCH'],
            ['pattern' => 'api/v1/space/<id:\d+>/unarchive', 'route' => '/rest/space/archive/unarchive', 'verb' => 'PATCH'],

            // Content
            ['pattern' => 'api/v1/content/find-by-container/<id:\d+>', 'route' => 'rest/content/content/find-by-container', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/content/container', 'route' => 'rest/content/container/list', 'verb' => 'GET'],
            //['pattern' => 'api/v1/content/<id:\d+>', 'route' => 'api/user/content/view', 'verb' => ['GET', 'HEAD']],
            //['pattern' => 'api/v1/content/', 'route' => 'api/user/content/delete', 'verb' => 'DELETE'],
            //['pattern' => 'api/v1/content/pin/<id:\d+>', 'route' => 'api/user/content/pin', 'verb' => 'POST'],
            //['pattern' => 'api/v1/content/unpin/<id:\d+>', 'route' => 'api/user/content/unpin', 'verb' => 'POST'],
            //['pattern' => 'api/v1/content/archive/<id:\d+>', 'route' => 'api/user/content/archive', 'verb' => 'POST'],
            //['pattern' => 'api/v1/content/unarchive/<id:\d+>', 'route' => 'api/user/content/unarchive', 'verb' => 'POST'],

            // Comment
            ['pattern' => 'api/v1/comment/<id:\d+>', 'route' => 'rest/comment/comment/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/comment/<id:\d+>', 'route' => 'rest/comment/comment/delete', 'verb' => 'DELETE'],

            // Like
            ['pattern' => 'api/v1/like/<id:\d+>', 'route' => 'rest/like/like/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/like/<id:\d+>', 'route' => 'rest/like/like/delete', 'verb' => 'DELETE'],
            ['pattern' => 'api/v1/like/findByRecord', 'route' => 'rest/like/like/find-by-record', 'verb' => 'GET'],

            // Post
            ['pattern' => 'api/v1/post/', 'route' => 'rest/post/post/find', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/post/<id:\d+>', 'route' => 'rest/post/post/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/post/<id:\d+>', 'route' => 'rest/post/post/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'api/v1/post/<id:\d+>', 'route' => 'rest/post/post/delete', 'verb' => ['DELETE']],
            ['pattern' => 'api/v1/post/container/<containerId:\d+>', 'route' => 'rest/post/post/create', 'verb' => 'POST'],
            ['pattern' => 'api/v1/post/container/<containerId:\d+>', 'route' => 'rest/post/post/find-by-container', 'verb' => 'GET'],

            // Topic
            ['pattern' => 'api/v1/topic/', 'route' => 'rest/topic/topic/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/topic/<id:\d+>', 'route' => 'rest/topic/topic/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/topic/<id:\d+>', 'route' => 'rest/topic/topic/update', 'verb' => ['PUT', 'PATCH']],
            ['pattern' => 'api/v1/topic/<id:\d+>', 'route' => 'rest/topic/topic/delete', 'verb' => ['DELETE']],
            ['pattern' => 'api/v1/topic/container/<containerId:\d+>', 'route' => 'rest/topic/topic/create', 'verb' => 'POST'],
            ['pattern' => 'api/v1/topic/container/<containerId:\d+>', 'route' => 'rest/topic/topic/find-by-container', 'verb' => 'GET'],

            // Activity
            ['pattern' => 'api/v1/activity/', 'route' => 'rest/activity/activity/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/activity/<id:\d+>', 'route' => 'rest/activity/activity/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/activity/container/<containerId:\d+>', 'route' => 'rest/activity/activity/find-by-container', 'verb' => ['GET', 'HEAD']],

            // Notification
            ['pattern' => 'api/v1/notification/', 'route' => 'rest/notification/notification/index', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/notification/unseen/', 'route' => 'rest/notification/notification/unseen', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/notification/mark-as-seen/', 'route' => 'rest/notification/notification/mark-as-seen', 'verb' => ['PATCH']],
            ['pattern' => 'api/v1/notification/<id:\d+>', 'route' => 'rest/notification/notification/view', 'verb' => ['GET', 'HEAD']],

            // File
            ['pattern' => 'api/v1/file/download/<id:\d+>', 'route' => 'rest/file/file/download', 'verb' => ['GET', 'HEAD']],

            // Wiki (temp)
            ['pattern' => 'api/v1/wiki/', 'route' => 'rest/wiki/wiki/find', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/wiki/<id:\d+>', 'route' => 'rest/wiki/wiki/view', 'verb' => ['GET', 'HEAD']],
            ['pattern' => 'api/v1/wiki/<id:\d+>', 'route' => 'rest/wiki/wiki/delete', 'verb' => ['DELETE']],
            ['pattern' => 'api/v1/wiki/container/<containerId:\d+>', 'route' => 'rest/wiki/wiki/find-by-container', 'verb' => 'GET'],

            // API Config
            ['pattern' => 'rest/admin/index', 'route' => 'rest/admin', 'verb' => ['POST', 'GET']],

            // Catch all to ensure verbs
            ['pattern' => 'rest/<tmpParam:.*>', 'route' => 'rest/error/notfound']

        ], true);
    }
}
