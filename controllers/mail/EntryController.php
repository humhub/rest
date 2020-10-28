<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2020 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\mail;

use humhub\modules\mail\models\forms\ReplyForm;
use humhub\modules\mail\models\MessageEntry as MessageEntryAlias;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\MailDefinitions;
use Yii;
use yii\web\HttpException;


/**
 * Class EntryController
 */
class EntryController extends BaseController
{

    /**
     * Get all entries of the conversation
     *
     * @param $messageId
     * @return array
     */
    public function actionIndex($messageId)
    {
        $results = [];
        $entriesQuery = MessageEntryAlias::find()->where(['message_id' => $messageId]);

        $pagination = $this->handlePagination($entriesQuery);
        foreach ($entriesQuery->all() as $entry) {
            $results[] = MailDefinitions::getMessageEntry($entry);
        }
        return $this->returnPagination($entriesQuery, $pagination, $results);
    }

    /**
     * Get an entry of the conversation
     *
     * @param $messageId
     * @param $entryId
     * @return array
     * @throws HttpException
     */
    public function actionView($messageId, $entryId)
    {
        $entry = MessageController::getMessageEntry($messageId, $entryId);
        return MailDefinitions::getMessageEntry($entry);
    }

    /**
     * Add an entry into conversation
     *
     * @param $messageId
     * @return array
     * @throws HttpException
     */
    public function actionAdd($messageId)
    {
        $message = MessageController::getMessage($messageId, true);

        $replyForm = new ReplyForm(['model' => $message]);
        $replyForm->load(['ReplyForm' => Yii::$app->request->post()]);

        if ($replyForm->save()) {
            return $this->actionView($messageId, $replyForm->reply->id);
        }

        if ($replyForm->hasErrors()) {
            return $this->returnError(400, 'Validation failed', $replyForm->getErrors());
        }

        Yii::error('Could not create validated entry for the conversation.', 'api');
        return $this->returnError(500, 'Internal error while save entry for the conversation!');
    }

    /**
     * Update entry of the conversation
     *
     * @param $messageId
     * @param $entryId
     * @return array
     * @throws HttpException
     */
    public function actionUpdate($messageId, $entryId)
    {
        $entry = MessageController::getMessageEntry($messageId, $entryId);

        $entry->load(['MessageEntry' => Yii::$app->request->post()]);

        if ($entry->save()) {
            return $this->actionView($messageId, $entryId);
        }

        if ($entry->hasErrors()) {
            return $this->returnError(400, 'Validation failed', $entry->getErrors());
        }

        Yii::error('Could not update validated entry of the conversation.', 'api');
        return $this->returnError(500, 'Internal error while update entry of the conversation!');
    }

    /**
     * Delete entry of the conversation
     *
     * @param $messageId
     * @param $entryId
     * @return array
     * @throws HttpException
     */
    public function actionDelete($messageId, $entryId)
    {
        $entry = MessageController::getMessageEntry($messageId, $entryId);

        if ($entry->delete()) {
            return $this->returnSuccess('Conversation entry successfully deleted!');
        }

        Yii::error('Could not delete validated entry from the conversation.', 'api');
        return $this->returnError(500, 'Internal error while delete entry from the conversation!');
    }
}