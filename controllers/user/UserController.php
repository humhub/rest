<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\user;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\user\models\forms\Login;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\models\User;
use Yii;
use yii\web\HttpException;

use humhub\modules\user\authclient\AuthClientHelpers;
use humhub\modules\user\authclient\interfaces\ApprovalBypass;
use humhub\modules\user\authclient\BaseFormAuth;
use humhub\modules\user\authclient\AuthAction;
use yii\authclient\BaseClient;


/**
 * Class AccountController
 */
class UserController extends BaseController
{

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
        $user = User::findOne(['id' => $id]);
        if ($user === null) {
            return $this->returnError(404, 'User not found!');
        }

        $user->scenario = 'editAdmin';
        $userData = Yii::$app->request->getBodyParam("account", []);
        if (!empty($userData)) {
            $user->load($userData, '');
            $user->validate();
        }

        $profile = null;
        $profileData = Yii::$app->request->getBodyParam("profile", []);

        if (!empty($profileData)) {
            $profile = $user->profile;
            $profile->scenario = 'editAdmin';
            $profile->load($profileData, '');
            $profile->validate();
        }

        $password = null;
        $passwordData = Yii::$app->request->getBodyParam("password", []);
        if (!empty($passwordData)) {
            $password = new Password();
            $password->scenario = 'registration';
            $password->load($passwordData, '');
            $password->newPasswordConfirm = $password->newPassword;
            $password->validate();
        }

        if ((!empty($userData) && $user->hasErrors()) ||
            ($password !== null && $password->hasErrors()) ||
            ($profile !== null && $profile->hasErrors())
        ) {
            return $this->returnError(400, 'Validation failed', [
                'profile' => ($profile !== null) ? $profile->getErrors() : null,
                'account' => $user->getErrors(),
                'password' => ($password !== null) ? $password->getErrors() : null,
            ]);
        }

        if (!$user->save()) {
            return $this->returnError(500, 'Internal error while save user!');
        }

        if ($profile !== null && !$profile->save()) {
            return $this->returnError(500, 'Internal error while save profile!');

        }

        if ($password !== null) {
            $password->user_id = $user->id;
            $password->setPassword($password->newPassword);
            if (!$password->save()) {
                return $this->returnError(500, 'Internal error while save new password!');
            }
        }

        return $this->actionView($user->id);
    }


    /**
     *
     * @return array
     * @throws HttpException
     */
    public function actionCreate()
    {
        $user = new User();
        $user->scenario = 'editAdmin';
        $user->load(Yii::$app->request->getBodyParam("account", []), '');
        $user->validate();

        $profile = new Profile();
        $profile->scenario = 'editAdmin';
        $profile->load(Yii::$app->request->getBodyParam("profile", []), '');
        $profile->validate();

        $password = new Password();
        $password->scenario = 'registration';
        $password->load(Yii::$app->request->getBodyParam("password", []), '');
        $password->newPasswordConfirm = $password->newPassword;
        $password->validate();

        if ($user->hasErrors() || $password->hasErrors() || $profile->hasErrors()) {
            return $this->returnError(400, 'Validation failed', [
                'password' => $password->getErrors(),
                'profile' => $profile->getErrors(),
                'account' => $user->getErrors(),
            ]);
        }

        if ($user->save()) {
            $profile->user_id = $user->id;
            $password->user_id = $user->id;
            $password->setPassword($password->newPassword);
            if ($profile->save() && $password->save()) {
                return $this->actionView($user->id);
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
    
    public function actionLogin(){
        
        // Login Form Handling
        $login = new Login;
        
        if ($login->load(Yii::$app->request->post()) && $login->validate()) {
            return $this->onAuthSuccess($login->authClient);
        }else{
            return $this->returnError(400, Yii::t('UserModule.base', 'User validation failed.'));
        }

        return $this->returnError(500, 'Internal error while save user!');
    }
    
    public function onAuthSuccess(BaseClient $authClient)
    {
         
        $attributes = $authClient->getUserAttributes();

        
        // Login existing user 
        $user = AuthClientHelpers::getUserByAuthClient($authClient);
       
        if ($user !== null) {
            return $this->actionView($user->id);
        }

        if (!$authClient instanceof ApprovalBypass && !Yii::$app->getModule('user')->settings->get('auth.anonymousRegistration')) {
            return $this->returnError(404, Yii::t('UserModule.base', "You're not registered."));
        }

        // Check if E-Mail is given
        if (!isset($attributes['email']) && Yii::$app->getModule('user')->emailRequired) {
           return $this->returnError(400, Yii::t('UserModule.base', 'Missing E-Mail Attribute from AuthClient.'));
        }

        if (!isset($attributes['id'])) {
           return $this->returnError(400, Yii::t('UserModule.base', 'Missing ID AuthClient Attribute from AuthClient.'));
        }

        // Check if e-mail is already taken
        if (isset($attributes['email']) && User::findOne(['email' => $attributes['email']]) !== null) {
            return $this->returnError(400, Yii::t('UserModule.base', 'User with the same email already exists but isn\'t linked to you. Login using your email first to link it.'));
        }
        
        return $this->returnError(400, Yii::t('UserModule.base', "Please check your data it is a bad request"));

        
    }
    


}