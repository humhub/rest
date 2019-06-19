<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 * @author Usama Ayaz <usama.ayaz@siliconplex.com>
 */

namespace humhub\modules\rest\controllers\friendship;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\FriendshipDefinitions;
use humhub\modules\friendship\models\Friendship;
use humhub\modules\user\models\User;
use Yii;
use yii\web\HttpException;

/**
 * Membership Handling Controller
 *
 * @property Module $module
 * @author luke
 */
class FriendshipController extends BaseController {

    /**
     * @inheritdoc
     * @throws HttpException
     */
    public function actionSendrequest() {


        $friend = User::findOne(['id' => Yii::$app->request->post('friendId')]);

        if ($friend === null) {
            throw new HttpException(404, 'Friend User not found!');
        }

        $user = User::findOne(['id' => Yii::$app->request->post('userId')]);

        if ($user === null) {
            throw new HttpException(404, 'User not found!');
        }

        if ($user->id === $friend->id) {
            throw new HttpException(404, 'You cannot send request to yourself!');
        }

        $check_friendship = Friendship::findOne(['user_id' => $friend->id, 'friend_user_id' => $user->id]);
        $check_request = Friendship::findOne(['user_id' => $user->id, 'friend_user_id' => $friend->id]);

        if ($check_request !== NULL && $check_friendship !== NULL) {
            throw new HttpException(404, 'Users are already friends!');
        }

        if ($check_request !== NULL) {
            throw new HttpException(404, 'Friend request already sent!');
        }

        if ($check_friendship !== NULL) {
            throw new HttpException(404, 'User has already sent request to you!');
        }

        $friendship = new Friendship();
        $friendship->user_id = $user->id;
        $friendship->friend_user_id = $friend->id;

        if ($friendship->hasErrors()) {
            return $this->returnError(400, 'Validation failed', [
                        'friendship' => $friendship->getErrors(),
            ]);
        }

        if ($friendship->save()) {
            return $this->actionView($friendship->id);
        }

        Yii::error('Could not send request.', 'api');
        return $this->returnError(500, 'Internal error while sending friend request!');
    }

    public function actionView($id) {

        $friendship = Friendship::findOne(['id' => $id]);
        if ($friendship === null) {
            return $friendship->returnError(404, 'Friendship not found!');
        }

        return FriendshipDefinitions::getFriendship($friendship);
    }

    public function actionAcceptrequest() {

        $friend = User::findOne(['id' => Yii::$app->request->post('friendId')]);

        if ($friend === null) {
            throw new HttpException(404, 'Friend User not found!');
        }

        $user = User::findOne(['id' => Yii::$app->request->post('userId')]);

        if ($user === null) {
            throw new HttpException(404, 'User not found!');
        }

        if ($user->id === $friend->id) {
            throw new HttpException(404, 'You cannot accept your own request!');
        }

        $check_friendship = Friendship::findOne(['user_id' => $friend->id, 'friend_user_id' => $user->id]);
        $check_request = Friendship::findOne(['user_id' => $user->id, 'friend_user_id' => $friend->id]);

        if ($check_friendship === NULL) {
            throw new HttpException(404, 'User has not sent request to you!');
        }

        if ($check_request !== NULL && $check_friendship !== NULL) {
            throw new HttpException(404, 'Users are already friends!');
        }

//        if ($check_request !== NULL) {
//            throw new HttpException(404, 'Friend request already sent!');
//        }


        $friendship = new Friendship();
        $friendship->user_id = $user->id;
        $friendship->friend_user_id = $friend->id;

        if ($friendship->hasErrors()) {
            return $this->returnError(400, 'Validation failed', [
                        'friendship' => $friendship->getErrors(),
            ]);
        }

        if ($friendship->save()) {
            return $this->actionView($friendship->id);
        }

        Yii::error('Could not accept request.', 'api');
        return $this->returnError(500, 'Internal error while accepting friend request!');
    }

    public function actionGetrequests($id) {
        $user = User::findOne(['id' => $id]);

        if ($user === null) {
            throw new HttpException(404, 'User not found!');
        }
        
        if ($user !== null) {
            $results = [];
            $user_requests = Friendship::findBySql('SELECT snd.* FROM user ufr'
                            . ' LEFT JOIN user_friendship snd ON ufr.id=snd.user_id AND snd.friend_user_id=' . $user->id . ''
                            . ' LEFT JOIN user_friendship recv ON ufr.id=recv.friend_user_id AND recv.user_id=' . $user->id . ''
                            . ' WHERE recv.id IS NULL AND snd.id IS NOT NULL'
                    );
            
               foreach ($user_requests->all() as $request) {
                $results[] = FriendshipDefinitions::getFriendShipForSend($request);
               }   
            
            
            return $results;
        }

        Yii::error('Could not accept request.', 'api');
        return $this->returnError(500, 'Internal error while accepting friend request!');
    }
    
    public function actionGetsentrequests($id) {
        $user = User::findOne(['id' => $id]);

        if ($user === null) {
            throw new HttpException(404, 'User not found!');
        }
        
        if ($user !== null) {
            $results = [];
            $user_requests = Friendship::findBySql('SELECT recv.* FROM user ufr'
                            . ' LEFT JOIN user_friendship snd ON ufr.id=snd.user_id AND snd.friend_user_id=' . $user->id . ''
                            . ' LEFT JOIN user_friendship recv ON ufr.id=recv.friend_user_id AND recv.user_id=' . $user->id . ''
                            . ' WHERE recv.id IS NOT NULL AND snd.id IS NULL'
                    );
             foreach ($user_requests->all() as $request) {
                $results[] = FriendshipDefinitions::getFriendShipForReceive($request);
               }   
            
            
            return $results;
        }

        Yii::error('Could not accept request.', 'api');
        return $this->returnError(500, 'Internal error while accepting friend request!');
    }

}
