<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components;

use humhub\libs\DbDateValidator;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\Content;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\file\models\File;
use humhub\modules\file\models\FileUpload;
use humhub\modules\rest\definitions\FileDefinitions;
use humhub\modules\tasks\controllers\rest\TasksController;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\topic\models\Topic;
use humhub\modules\topic\permissions\AddTopic;
use Yii;
use yii\base\DynamicModel;
use yii\web\HttpException;
use yii\web\UploadedFile;

/**
 * BaseContentController provides basic CRUD operations for HumHub content records
 *
 * @package humhub\modules\rest\components
 */

abstract class BaseContentController extends BaseController
{
    /**
     * @return string returns the class name of the active record
     */
    abstract public function getContentActiveRecordClass();

    /**
     * @param ContentActiveRecord $contentRecord
     * @return array returns the array of the content definition
     */
    abstract public function returnContentDefinition(ContentActiveRecord $contentRecord);

    /**
     * @param $id
     * @return array
     */
    public function actionView($id)
    {
        $class = $this->getContentActiveRecordClass();

        $contentRecord = $class::findOne(['id' => $id]);
        if ($contentRecord === null) {
            return $this->returnError(404, 'Requested content not found!');
        }
        if (!$contentRecord->content->canView()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        return $this->returnContentDefinition($contentRecord);
    }

    /**
     * Finds content
     *
     * @return array the rest output
     */
    public function actionFind()
    {
        $class = $this->getContentActiveRecordClass();

        /** @var ActiveQueryContent $query */
        $query = $class::find()->joinWith('content')->orderBy(['content.created_at' => SORT_DESC])->readable();

        $pagination = $this->handlePagination($query);

        $results = [];
        foreach ($query->all() as $contentRecord) {
            /** @var ContentActiveRecord $contentRecord */
            $results[] = $this->returnContentDefinition($contentRecord);
        }

        return $this->returnPagination($query, $pagination, $results);
    }

    /**
     * Finds content by given container
     *
     * @param int $containerId the id of the content container
     * @return array the rest output
     * @throws \yii\db\IntegrityException
     */
    public function actionFindByContainer($containerId)
    {
        $contentContainer = ContentContainer::findOne(['id' => $containerId]);
        if ($contentContainer === null) {
            return $this->returnError(404, 'Content container not found!');
        }

        $class = $this->getContentActiveRecordClass();

        /** @var ActiveQueryContent $query */
        $query = $class::find()->contentContainer($contentContainer->getPolymorphicRelation())->orderBy(['content.created_at' => SORT_DESC])->readable();

        ContentDefinitions::handleTopicsParam($query, $containerId);

        $pagination = $this->handlePagination($query);

        $results = [];

        foreach ($query->all() as $contentRecord) {
            /** @var ContentActiveRecord $contentRecord */
            $results[] = $this->returnContentDefinition($contentRecord);
        }

        return $this->returnPagination($query, $pagination, $results);
    }

    /**
     * Creates a content in a content container
     *
     * @param $containerId
     * @return array
     * @throws \yii\db\IntegrityException
     * @throws \yii\base\InvalidConfigException
     */
    public function actionCreate($containerId)
    {
        $containerRecord = ContentContainer::findOne(['id' => $containerId]);
        if ($containerRecord === null) {
            return $this->returnError(404, 'Content container not found!');
        }

        /** @var ContentContainerActiveRecord $container */
        $container = $containerRecord->getPolymorphicRelation();

        /** @var ContentActiveRecord $contentRecord */
        $contentRecord = Yii::createObject(['class' => $this->getContentActiveRecordClass()]);

        $contentRecord->content->container = $container;

        if ($this->saveRecord($contentRecord)) {
            return $this->returnContentDefinition($contentRecord);
        }

        return $this->returnError(400, 'Validation failed', ['post' => $contentRecord->getErrors()]);
    }

    public function actionUpdate($id)
    {
        $class = $this->getContentActiveRecordClass();

        /* @var ContentActiveRecord $contentRecord */
        $contentRecord = $class::findOne(['id' => $id]);
        if ($contentRecord === null) {
            return $this->returnError(404, 'Request object not found!');
        }
        if (!$contentRecord->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to update this content!');
        }

        if ($this->saveRecord($contentRecord)) {
            return $this->returnContentDefinition($contentRecord);
        }

        return $this->returnError(400, 'Validation failed', ['post' => $contentRecord->getErrors()]);
    }

    /**
     * Deletes the content record by given id
     *
     * @param $id
     * @return array
     */
    public function actionDelete($id)
    {
        $class = $this->getContentActiveRecordClass();

        $contentRecord = $class::findOne(['id' => $id]);
        if ($contentRecord === null) {
            return $this->returnError(404, 'Content record not found!');
        }
        if (!$contentRecord->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to delete this content!');
        }

        if ($contentRecord->delete()) {
            return $this->returnSuccess('Successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete content!');
    }

    /**
     * Deletes all content records by given container
     *
     * @param $containerId
     * @return array
     */
    public function actionDeleteByContainer($containerId)
    {
        $contentContainer = ContentContainer::findOne(['id' => $containerId]);
        if ($contentContainer === null) {
            return $this->returnError(404, 'Content container not found!');
        }

        $class = $this->getContentActiveRecordClass();

        /** @var ActiveQueryContent $query */
        $query = $class::find()->contentContainer($contentContainer->getPolymorphicRelation())->readable();

        $deletedRecordsCount = 0;
        foreach ($query->all() as $record) {
            if (!$record->delete()) {
                return $this->returnError(500, 'Internal error while delete content!');
            }
            $deletedRecordsCount++;
        }

        return $this->returnSuccess($deletedRecordsCount ? $deletedRecordsCount . ' records successfully deleted!' : 'No records deleted.');
    }

    public function actionAttachFiles($id)
    {
        /* @var ContentActiveRecord $class */
        $class = $this->getContentActiveRecordClass();
        $contentRecord = $class::findOne(['id' => $id]);

        if ($contentRecord === null || $contentRecord->content === null) {
            return $this->returnError(404, 'Content record not found!');
        }
        if (!$contentRecord->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to upload files to this content!');
        }

        $uploadedFiles = UploadedFile::getInstancesByName('files');

        if (empty($uploadedFiles)) {
            return $this->returnError(400, 'No files to upload.');
        }

        $files = [];
        File::getDb()->transaction(function ($db) use ($uploadedFiles, & $files) {
            $hiddenInStream = Yii::$app->request->post('hiddenInStream', []);
            foreach ($uploadedFiles as $cFile) {
                $file = Yii::createObject(FileUpload::class);
                $file->setUploadedFile($cFile);
                if (in_array($file->file_name, $hiddenInStream)) {
                    $file->show_in_stream = 0;
                }
                if (!$file->save()) {
                    return false;
                }
                $files[] = $file;
            }
            return true;
        });

        if (empty($files)) {
            return $this->returnError(500, 'Internal error while saving file.');
        }

        $contentRecord->fileManager->attach($files);

        $fileDefinitions = [];
        foreach ($files as $file) {
            $fileDefinitions[] = FileDefinitions::getFile($file);
        }

        return $this->returnSuccess('Files successfully uploaded.', 200, ['files' => $fileDefinitions]);
    }

    public function actionRemoveFile($id, $fileId)
    {
        $class = $this->getContentActiveRecordClass();
        $file = File::findOne(['id' => $fileId]);
        $contentRecord = $class::findOne(['id' => $id]);

        if ($file == null || $contentRecord == null) {
            return $this->returnError(404, 'Could not find requested content record or file!');
        }
        if (!$contentRecord->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to remove files from this content!');
        }

        $isAssignedTo = $file->object_model === $class && $file->object_id == $contentRecord->getPrimaryKey();

        if (! $isAssignedTo || ! $file->canDelete()) {
            return $this->returnError(403, 'Insufficient permissions!');
        }
        if ($file->delete()) {
            return $this->returnSuccess('File successfully removed.');
        } else {
            return $this->returnError(500, 'Internal error while removing file.');
        }
    }

    protected function saveRecord(ContentActiveRecord $contentRecord): bool
    {
        $data = Yii::$app->request->getBodyParam('data', []);
        return $contentRecord->load($data, '')
            && $contentRecord->save()
            && $this->updateContent($contentRecord, $data);
    }

    protected function updateContent($activeRecord, $data): bool
    {
        if (!($activeRecord instanceof ContentActiveRecord)) {
            return false;
        }

        if (empty($data['content']) || !is_array($data['content'])) {
            return true;
        }

        if (!$this->updateTopics($activeRecord, $data['content'])) {
            return false;
        }

        if (!$this->updateMetadata($activeRecord, $data['content'])) {
            return false;
        }

        return true;
    }

    protected function updateTopics(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['topics'])) {
            return true;
        }

        if (!is_array($data['topics'])) {
            $activeRecord->addError('topics', 'Wrong field "topics" format!');
            return false;
        }

        foreach ($data['topics'] as $t => $topic) {
            if (!isset($topic['name'])) {
                $activeRecord->addError('topics', 'Wrong topic #' . ($t + 1) . ' format!');
                return false;
            }
        }

        Topic::deleteContentRelations($activeRecord->content);

        if (empty($data['topics'])) {
            return true;
        }

        $canAdd = $activeRecord->content->container->can(AddTopic::class);

        $updatedTopics = [];
        foreach ($data['topics'] as $topic) {
            $topic = Topic::findOne($topicData = [
                'module_id' => 'topic',
                'contentcontainer_id' => $activeRecord->content->contentcontainer_id,
                'name' => $topic['name'],
            ]);

            if ($topic) {
                $updatedTopics[] = $topic;
            } elseif ($canAdd) {
                $topic = new Topic($topicData);
                if ($topic->save()) {
                    $updatedTopics[] = $topic;
                }
            }
        }

        $activeRecord->content->addTags($updatedTopics);

        return true;
    }

    protected function updateMetadata(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['metadata'])) {
            return true;
        }

        if (!$this->updateVisibility($activeRecord, $data['metadata'])) {
            return false;
        }

        if (!$this->updateVisibility($activeRecord, $data['metadata'])) {
            return false;
        }

        if (!$this->updateArchived($activeRecord, $data['metadata'])) {
            return false;
        }

        if (!$this->updatePinned($activeRecord, $data['metadata'])) {
            return false;
        }

        if (!$this->updateLockedComments($activeRecord, $data['metadata'])) {
            return false;
        }

        if (!$this->updateScheduledAt($activeRecord, $data['metadata'])) {
            return false;
        }

        if (Yii::$app->user->identity->isSystemAdmin() && !$this->updateCreatedAt($activeRecord, $data['metadata'])) {
            return false;
        }

        return true;
    }

    protected function updatePinned(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['pinned'])) {
            return true;
        }

        if (!in_array($data['pinned'], [0, 1])) {
            $activeRecord->addError('pinned', 'Wrong field "pinned" value!');
            return false;
        }

        if ($data['pinned']) {
            if (!$activeRecord->content->canPin()) {
                $activeRecord->addError('archived', 'You cannot pin the Content!');
                return false;
            }
            $activeRecord->content->pin();
        } else {
            $activeRecord->content->unpin();
        }

        return true;
    }

    protected function updateArchived(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['archived'])) {
            return true;
        }

        if (!in_array($data['archived'], [0, 1])) {
            $activeRecord->addError('archived', 'Wrong field "archived" value!');
            return false;
        }

        if (!$activeRecord->content->canArchive()) {
            $activeRecord->addError('archived', 'You cannot archive the Content!');
            return false;
        }

        if ($data['archived']) {
            $activeRecord->content->archive();
        } else {
            $activeRecord->content->unarchive();
        }

        return true;
    }

    protected function updateVisibility(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['visibility'])) {
            return true;
        }

        if (!in_array($data['visibility'], [Content::VISIBILITY_PRIVATE, Content::VISIBILITY_PUBLIC, Content::VISIBILITY_OWNER])) {
            $activeRecord->addError('archived', 'Wrong field "visibility" value!');
            return false;
        }

        $activeRecord->content->visibility = $data['visibility'];
        return $activeRecord->content->save();
    }

    protected function updateLockedComments(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['locked_comments'])) {
            return true;
        }

        if (!in_array($data['locked_comments'], [0, 1])) {
            $activeRecord->addError('locked_comments', 'Wrong field "locked_comments" value!');
            return false;
        }

        if (!$activeRecord->content->canLockComments()) {
            $activeRecord->addError('locked_comments', 'You cannot lock comments of the Content!');
            return false;
        }

        $activeRecord->content->locked_comments = $data['locked_comments'];
        return $activeRecord->content->save();
    }

    protected function updateScheduledAt(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['scheduled_at'])) {
            return true;
        }

        $validator = DynamicModel::validateData([
            'scheduled_at' => $data['scheduled_at'],
        ], [
            ['scheduled_at', 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ]);

        if (!$validator->validate()) {
            $activeRecord->addError('scheduled_at', $validator->getFirstError('scheduled_at'));

            return false;
        }

        $activeRecord->content->getStateService()->schedule($data['scheduled_at']);

        return $activeRecord->content->save();
    }

    public function updateCreatedAt(ContentActiveRecord $activeRecord, array $data): bool
    {
        if (!isset($data['created_at'])) {
            return true;
        }

        $validator = DynamicModel::validateData([
            'created_at' => $data['created_at'],
        ], [
            ['created_at', 'datetime', 'format' => 'php:Y-m-d H:i:s'],
        ]);

        if (!$validator->validate()) {
            $activeRecord->addError('created_at', $validator->getFirstError('created_at'));

            return false;
        }

        $activeRecord->content->created_at = $data['created_at'];

        return $activeRecord->content->save();
    }
}
