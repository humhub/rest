<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\user;

use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\rest\components\BaseController;
use humhub\modules\user\models\Session;
use humhub\modules\user\models\User;


class SessionController extends BaseController
{

    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            ['permissions' => [ManageUsers::class]],
        ];
    }

    public function actionDeleteFromUser($id)
    {
        $user = User::findOne(['id' => $id]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        $count = Session::deleteAll(['user_id' => $user->id]);
        return $this->returnSuccess($count . ' user sessions deleted!');

    }

}
