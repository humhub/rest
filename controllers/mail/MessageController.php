<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\mail;

use humhub\modules\mail\models\forms\CreateMessage;
use humhub\modules\mail\models\Message;
use humhub\modules\mail\models\MessageEntry;
use humhub\modules\mail\permissions\SendMail;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\MailDefinitions;
use humhub\modules\user\models\User;
use Yii;
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
            throw new HttpException(403, 'You must be a participant of the conversation.');
        }

        return $message;
    }

    /**
     * Get user by id
     *
     * @param $id
     * @return User
     * @throws HttpException
     */
    public static function getUser($id)
    {
        $user = User::findOne(['id' => $id]);
        if ($user === null) {
            throw new HttpException(404, 'User not found!');
        }
        return $user;
    }

    /**
     * Get participant of the conversation
     *
     * @param $messageId
     * @param $userId
     * @param null|boolean $isParticipant
     * @return array [Message, User]
     * @throws HttpException
     */
    public static function getMessageUser($messageId, $userId, $isParticipant = null)
    {
        $message = static::getMessage($messageId);
        $user = static::getUser($userId);

        if ($isParticipant === false && $message->isParticipant($user)) {
            throw new HttpException(400, 'User is already a participant of the conversation.');
        } else if ($isParticipant === true && !$message->isParticipant($user)) {
            throw new HttpException(400, 'User is not a participant of the conversation.');
        }

        return [$message, $user];
    }

    /**
     * Get entry of the conversation
     *
     * @param $messageId
     * @param $entryId
     * @return MessageEntry
     * @throws HttpException
     */
    public static function getMessageEntry($messageId, $entryId)
    {
        $message = static::getMessage($messageId, true);

        $entry = MessageEntry::findOne([
            'id' => $entryId,
            'message_id' => $message->id
        ]);

        if (!$entry) {
            throw new HttpException(404, 'Conversation entry not found!');
        }

        if (!$entry->canEdit()) {
            throw new HttpException(403, 'You cannot edit the conversation entry!');
        }

        return $entry;
    }
}