<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\user;

use humhub\modules\rest\components\BaseController;
use humhub\modules\user\models\forms\Invite as InviteForm;
use humhub\modules\user\models\Invite;
use Yii;


/**
 * Class InviteController
 */
class InviteController extends BaseController
{
    public function actionIndex()
    {
        $model = new InviteForm();

        if ($model->load(Yii::$app->request->getBodyParams(), '') && $model->validate()) {
            foreach ($model->getEmails() as $email) {
                $this->createInvite($email);
            }

            return $this->returnSuccess('Users have been invited.');
        }

        return $this->returnError(404, 'Error: ' . implode(' ', $model->getErrorSummary(true)));
    }

    protected function createInvite($email)
    {
        $userInvite = new Invite();
        $userInvite->email = $email;
        $userInvite->source = Invite::SOURCE_INVITE;
        $userInvite->user_originator_id = Yii::$app->user->id;

        $existingInvite = Invite::findOne(['email' => $email]);
        if ($existingInvite !== null) {
            $userInvite->token = $existingInvite->token;
            $existingInvite->delete();
        }

        $userInvite->save();
        $userInvite->sendInviteMail();
    }
}