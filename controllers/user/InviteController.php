<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\user;

use humhub\modules\rest\components\BaseController;
use humhub\modules\user\models\Invite;
use humhub\modules\user\models\User;
use Yii;
use yii\validators\EmailValidator;


/**
 * Class InviteController
 */
class InviteController extends BaseController
{
    public function actionIndex()
    {
        $emails = (array)Yii::$app->request->post('emails');
        if (!$emails) {
            return $this->returnError(404, 'Please provide an array of emails in the json format');
        }

        $errors = [];
        foreach ($emails as $email) {
            $validator = new EmailValidator();
            if (!$validator->validate($email)) {
                $errors[] = $email . ' is not valid!';
            }
            if (User::findOne(['email' => $email]) !== null) {
                $errors[] = $email . ' is already registered!';
            }
        }
        if ($errors) {
            return $this->returnError(404, implode(' | ', $errors));
        }

        foreach ($emails as $email) {
            $this->createInvite($email);
        }
        return $this->returnSuccess(count($emails) . ' users have been invited.');
    }

    protected function createInvite($email)
    {
        $userInvite = new Invite();
        $userInvite->email = $email;
        $userInvite->source = Invite::SOURCE_INVITE;
        $userInvite->user_originator_id = Yii::$app->user->id;
        $userInvite->save();
        $userInvite->sendInviteMail();
    }
}