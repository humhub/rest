<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\mail;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\MailDefinitions;
use Yii;
use yii\web\HttpException;


/**
 * Class UserController
 */
class UserController extends BaseController
{

    /**
     * Get all participants of the conversation
     *
     * @param $messageId
     * @return array
     * @throws HttpException
     */
    public function actionIndex($messageId)
    {
        $message = MessageController::getMessage($messageId);
        return MailDefinitions::getMessageUsers($message);
    }

    /**
     * Add a participant into conversation
     *
     * @param $messageId
     * @param $userId
     * @return array
     * @throws HttpException
     */
    public function actionAdd($messageId, $userId)
    {
        list($message, $user) = MessageController::getMessageUser($messageId, $userId, false);

        if ($message->addRecepient($user)) {
            return $this->actionIndex($messageId);
        }

        Yii::error('Could not add a participant into conversation.', 'api');
        return $this->returnError(500, 'Internal error while add a participant into conversation!');
    }

    /**
     * Leave a participant from conversation
     *
     * @param $messageId
     * @param $userId
     * @return array
     * @throws HttpException
     */
    public function actionLeave($messageId, $userId)
    {
        list($message) = MessageController::getMessageUser($messageId, $userId, true);

        $message->leave($userId);

        return $this->actionIndex($messageId);
    }
}