<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\user;

use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\rest\models\ApiUser;
use humhub\modules\rest\models\UserAuthForm;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use Yii;
use yii\web\HttpException;

/**
 * Class AccountController
 */
class UserController extends BaseController
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

    public function actionIndex()
    {
        $results = [];
        $query = User::find();

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $user) {
            $results[] = UserDefinitions::getUser($user);
        }
        return $this->returnPagination($query, $pagination, $results);
    }


    /**
     * Get User by username
     *
     * @param string $username the username searched
     * @return array
     * @throws HttpException
     */
    public function actionGetByUsername($username)
    {
        $user = User::findOne(['username' => $username]);

        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        return $this->actionView($user->id);
    }

    /**
     * Get User by email
     *
     * @param string $email the email searched
     * @return array
     * @throws HttpException
     */
    public function actionGetByEmail($email)
    {
        $user = User::findOne(['email' => $email]);

        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        return $this->actionView($user->id);
    }

    /**
     * Get User by authclient
     *
     * @param string $name Name of auth client
     * @param string $id ID of auth client
     * @return array
     * @throws HttpException
     */
    public function actionGetByAuthclient($name, $id)
    {
        $user = User::find()
            ->alias('u')
            ->joinWith('auths a', false)
            ->where(['u.auth_mode' => $name, 'u.authclient_id' => $id])
            ->orWhere(['a.source' => $name, 'a.source_id' => $id])
            ->one();

        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        return $this->actionView($user->id);
    }

    public function actionView($id)
    {
        $user = User::findOne(['id' => $id]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        return UserDefinitions::getUser($user);
    }

    public function actionUpdate($id)
    {
        $apiUser = ApiUser::findOne(['id' => $id]);
        if ($apiUser->user === null) {
            return $this->returnError(404, 'User not found!');
        }

        $userData = Yii::$app->request->getBodyParam('account', []);
        if (!empty($userData)) {
            if (Yii::$app->user->isAdmin() || !$apiUser->user->isSystemAdmin()) {
                $apiUser->user->scenario = User::SCENARIO_EDIT_ADMIN;
            }
            $apiUser->load($userData, '');
            $apiUser->validate();
        }

        $profile = null;
        $profileData = Yii::$app->request->getBodyParam('profile', []);

        if (!empty($profileData)) {
            $profile = $apiUser->user->profile;
            if (Yii::$app->user->isAdmin() || !$apiUser->user->isSystemAdmin()) {
                $profile->scenario = Profile::SCENARIO_EDIT_ADMIN;
            }
            $profile->load($profileData, '');
            $profile->validate();
        }

        $password = null;
        $passwordData = Yii::$app->request->getBodyParam('password', []);
        if (!empty($passwordData)) {
            $password = new Password();
            $password->scenario = 'registration';
            $password->load($passwordData, '');
            $password->newPasswordConfirm = $password->newPassword;
            $password->validate();
        }

        if ((!empty($userData) && $apiUser->hasErrors()) ||
            ($password !== null && $password->hasErrors()) ||
            ($profile !== null && $profile->hasErrors())
        ) {
            return $this->returnError(400, 'Validation failed', [
                'profile' => ($profile !== null) ? $profile->getErrors() : null,
                'account' => $apiUser->getErrors(),
                'password' => ($password !== null) ? $password->getErrors() : null,
            ]);
        }

        if (!$apiUser->save()) {
            return $this->returnError(500, 'Internal error while save user!');
        }

        if ($profile !== null && !$profile->save()) {
            return $this->returnError(500, 'Internal error while save profile!');

        }

        if ($password !== null) {
            $password->user_id = $apiUser->id;
            $password->setPassword($password->newPassword);
            if (!$password->save()) {
                return $this->returnError(500, 'Internal error while save new password!');
            }
        }

        return $this->actionView($apiUser->id);
    }


    /**
     *
     * @return array
     * @throws HttpException
     */
    public function actionCreate()
    {
        $apiUser = new ApiUser();
        if (Yii::$app->user->isAdmin()) {
            $apiUser->user->scenario = User::SCENARIO_EDIT_ADMIN;
        }
        $apiUser->load(Yii::$app->request->getBodyParam('account', []), '');
        $apiUser->validate();

        $profile = new Profile();
        $profile->scenario = Profile::SCENARIO_EDIT_ADMIN;
        $profile->load(Yii::$app->request->getBodyParam('profile', []), '');
        $profile->validate();

        $password = new Password();
        $password->scenario = 'registration';

        if ($password->load(Yii::$app->request->getBodyParam('password', []), '')) {
            $password->newPasswordConfirm = $password->newPassword;
            $password->validate();
        }

        if ($apiUser->hasErrors() || $password->hasErrors() || $profile->hasErrors()) {
            return $this->returnError(400, 'Validation failed', [
                'password' => $password->getErrors(),
                'profile' => $profile->getErrors(),
                'account' => $apiUser->getErrors(),
            ]);
        }

        if ($apiUser->save()) {
            $profile->user_id = $apiUser->id;
            $password->user_id = $apiUser->id;

            if ($password->newPassword) {
                $password->setPassword($password->newPassword);
                if ($password->save() && $password->mustChangePassword) {
                    $apiUser->user->setMustChangePassword(true);
                }
            }

            if ($profile->save()) {
                return $this->actionView($apiUser->id);
            }
        }

        Yii::error('Could not create validated user.', 'api');

        return $this->returnError(500, 'Internal error while save user!');
    }

    public function actionDelete($id)
    {
        $user = User::findOne(['id' => $id]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        if ($user->softDelete()) {
            return $this->returnSuccess('User successfully soft deleted!');
        }
        return $this->returnError(500, 'Internal error while soft delete user!');
    }


    public function actionHardDelete($id)
    {
        $user = User::findOne(['id' => $id]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        if ($user->delete()) {
            return $this->returnSuccess('User successfully deleted!');
        }

        return $this->returnError(500, 'Internal error while soft delete user!');
    }

    public function actionAddAuthClient($id)
    {
        if (!Yii::$app->user->isAdmin()) {
            return $this->returnError(403, Yii::t('RestModule.base', 'You are not allowed to do this action!'));
        }

        $userAuth = new UserAuthForm();
        $userAuth->load(Yii::$app->request->bodyParams, '');
        $userAuth->userId = $id;
        $userAuth->save();

        if ($userAuth->hasErrors()) {
            return $this->returnError(422, Yii::t('RestModule.base', 'Validation failed'), $userAuth->errors);
        }

        return $this->returnSuccess('successfully Saved!');
    }
}
