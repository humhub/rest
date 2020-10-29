<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\mail;

use humhub\modules\mail\models\forms\CreateMessage;
use humhub\modules\mail\models\Message;
use humhub\modules\mail\permissions\SendMail;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\MailDefinitions;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;


/**
 * Class MessageController
 */
class MessageController extends BaseController
{

    /**
     * Get list of mail conversations
     *
     * @return array
     */
    public function actionIndex()
    {
        $results = [];
        $messagesQuery = Message::find();

        $pagination = $this->handlePagination($messagesQuery);
        foreach ($messagesQuery->all() as $message) {
            $results[] = MailDefinitions::getMessage($message);
        }
        return $this->returnPagination($messagesQuery, $pagination, $results);
    }

    /**
     * Get a mail conversation by id
     *
     * @param $id
     * @return array
     * @throws HttpException
     */
    public function actionView($id)
    {
        $message = static::getMessage($id);
        return MailDefinitions::getMessage($message);
    }

    /**
     * Create a mail conversation
     *
     * @return array
     * @throws \Throwable
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->isAdmin() && !Yii::$app->user->getPermissionManager()->can(SendMail::class)) {
            return $this->returnError(403, 'You cannot create conversations!');
        }

        $message = new CreateMessage();
        $message->load(['CreateMessage' => Yii::$app->request->post()]);

        if ($message->save()) {
            return $this->actionView($message->messageInstance->id);
        }

        if ($message->hasErrors()) {
            return $this->returnError(400, 'Validation failed', $message->getErrors());
        }

        Yii::error('Could not create validated conversation.', 'api');
        return $this->returnError(500, 'Internal error while save conversation!');
    }

    /**
     * Get conversation by id
     *
     * @param $id
     * @param boolean $checkParticipant
     * @return Message
     * @throws HttpException
     */
    public static function getMessage($id, $checkParticipant = false)
    {
        $message = Message::findOne(['id' => $id]);
        if ($message === null) {
            throw new HttpException(404, 'Message not found!');
        }

        if ($checkParticipant && !$message->isParticipant(Yii::$app->user)) {
            throw new ForbiddenHttpException('You must be a participant of the conversation.');
        }

        return $message;
    }
}