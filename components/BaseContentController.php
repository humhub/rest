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
use humhub\modules\content\models\ContentContainer;
use humhub\modules\file\models\File;
use humhub\modules\file\models\FileUpload;
use humhub\modules\tasks\controllers\rest\TasksController;
use humhub\modules\rest\definitions\ContentDefinitions;
use Yii;
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
     * @param integer $containerId the id of the content container
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
        $contentRecord->load(Yii::$app->request->getBodyParam('data', []), '');

        if ($contentRecord->save()) {
            return $this->returnContentDefinition($contentRecord);
        }

        return $this->returnError(400, 'Validation failed', ['post' => $contentRecord->getErrors()]);
    }

    public function actionUpdate($id)
    {
        $class = $this->getContentActiveRecordClass();

        $contentRecord = $class::findOne(['id' => $id]);
        if ($contentRecord === null) {
            return $this->returnError(404, 'Request object not found!');
        }
        if (!$contentRecord->content->canEdit()) {
            return $this->returnError(403, 'You are not allowed to update this content!');
        }

        if ($contentRecord->load(Yii::$app->request->getBodyParam('data', []), '') && $contentRecord->save()) {
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
        $class = $this->getContentActiveRecordClass();
        $contentRecord = $class::findOne(['id' => $id]);
        if ($contentRecord === null) {
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
            foreach ($uploadedFiles as $cFile) {
                $file = Yii::createObject(FileUpload::class);
                $file->setUploadedFile($cFile);
                if (! $file->save()) {
                    return false;
                }
                $files[] = $file->guid;
            }
            return true;
        });

        if (! empty($files)) {
            $contentRecord->fileManager->attach($files);
            return $this->returnSuccess('Files successfully uploaded.');
        } else {
            return $this->returnError(500, 'Internal error while saving file.');
        }
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

    /**********************************************************************************
     * Helpers
     *********************************************************************************
     *
    /*
     * Prepare date formats for Calendar entries and Tasks
     *
     * @param array $requestParams
     * @param string $formName
     * @param string $modelName
     * @return array
     * @throws HttpException
     */
    protected function prepareRequestParams(array $requestParams, string $formName, string $modelName): array
    {
        if ($this instanceof TasksController && empty($requestParams[$modelName]['scheduling'])) {
            return $requestParams;
        }

        if (empty($requestParams[$formName]['start_date'])) {
            throw new HttpException(400, 'Start date cannot be blank');
        } else {
            $requestParams[$modelName]['all_day'] = 0;
        }

        if (empty($requestParams[$formName]['end_date'])) {
            throw new HttpException(400, 'End date cannot be blank');
        } else {
            $requestParams[$modelName]['all_day'] = 0;
        }

        if (!preg_match(DbDateValidator::REGEX_DBFORMAT_DATE, $requestParams[$formName]['start_date']) &&
            !preg_match(DbDateValidator::REGEX_DBFORMAT_DATETIME, $requestParams[$formName]['start_date'])) {
            throw new HttpException(400, 'Wrong start date format.');
        }

        if (!preg_match(DbDateValidator::REGEX_DBFORMAT_DATE, $requestParams[$formName]['end_date']) &&
            !preg_match(DbDateValidator::REGEX_DBFORMAT_DATETIME, $requestParams[$formName]['end_date'])) {
            throw new HttpException(400, 'Wrong end date format.');
        }

        return $requestParams;
    }
}