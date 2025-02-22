<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\user;

use humhub\modules\admin\permissions\ManageUsers;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\models\Invite;
use humhub\modules\rest\definitions\InviteDefinitions;
use humhub\modules\user\models\User;
use Yii;
use yii\validators\EmailValidator;
use yii\helpers\ArrayHelper;

/**
 * Class InviteController
 */
class InviteController extends BaseController
{
    protected function getAccessRules()
    {
        return [
            ['permissions' => [ManageUsers::class]],
        ];
    }

    public function actionIndex()
    {
        $emails = (array) Yii::$app->request->post('emails');
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

    public function actionList()
    {
        $query = Invite::find()
            ->where(['source' => Invite::SOURCE_INVITE])
            ->with(['space', 'originator', 'createdBy', 'updatedBy']);

        $pagination = $this->handlePagination($query, 10);

        $results = ArrayHelper::getColumn($query->all(), function (Invite $invite) {
            return InviteDefinitions::getInvite($invite);
        });

        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionResend($id)
    {
        $userInvite = Invite::find()->where(['id' => $id, 'source' => Invite::SOURCE_INVITE])->one();

        if (!$userInvite) {
            return $this->returnError(404, 'Invite not found!');
        }

        $userInvite->save();
        $userInvite->sendInviteMail();

        return $this->returnSuccess('Invite has been resent.');
    }

    public function actionCancel($id)
    {
        $userInvite = Invite::find()->where(['id' => $id, 'source' => Invite::SOURCE_INVITE])->one();

        if (!$userInvite) {
            return $this->returnError(404, 'Invite not found!');
        }

        $userInvite->delete();

        return $this->returnSuccess('Invite has been canceled.');
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
