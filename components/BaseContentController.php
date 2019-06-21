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
use humhub\modules\rest\controllers\task\TaskController;
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
        $query = $class::find()->joinWith('content')->orderBy(['content.created_at' => SORT_DESC]);


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
        $query = $class::find()->contentContainer($contentContainer->getPolymorphicRelation())->orderBy(['content.created_at' => SORT_DESC]);

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
        $records = $class::find()->contentContainer($contentContainer->getPolymorphicRelation())->all();

        foreach ($records as $record) {
            if (!$record->delete()) {
                return $this->returnError(500, 'Internal error while delete content!');
            }
        }

        return $this->returnSuccess('Records successfully deleted!');
    }

    public function actionAttachFiles($id)
    {
        $class = $this->getContentActiveRecordClass();
        $contentRecord = $class::findOne(['id' => $id]);
        if ($contentRecord === null) {
            return $this->returnError(404, 'Content record not found!');
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
     * /*
     * @param $requestParams
     * @param $formName
     * @param $modelName
     * @return array
     * @throws HttpException
     */
    protected function prepareRequestParams($requestParams, $formName, $modelName)
    {
        if ($this instanceof TaskController && empty($requestParams[$modelName]['scheduling'])) {
            return $requestParams;
        }
        
        if (empty($requestParams[$formName]['start_date']) || empty($requestParams[$formName]['end_date'])) {
            $message = empty($requestParams[$formName]['start_date']) ? 'Start ' : 'End ';
            $message .=  'date cannot be blank';
            throw new HttpException(400, $message);
        }

        if (! empty($requestParams[$formName]['start_time'])) {
            $requestParams[$modelName]['all_day'] = 0;
            $requestParams[$formName]['start_date'] .= ' ' . $requestParams[$formName]['start_time'] . ':00';
        } else {
            $requestParams[$formName]['start_date'] .= ' 00:00:00';
        }

        if (! empty($requestParams[$formName]['end_time'])) {
            $requestParams[$modelName]['all_day'] = 0;
            $requestParams[$formName]['end_date'] .= ' ' . $requestParams[$formName]['end_time'] . ':00';
        } else {
            $requestParams[$formName]['end_date'] .= ' 23:59:00';
        }

        if (preg_match(DbDateValidator::REGEX_DBFORMAT_DATE, $requestParams[$formName]['start_date']) ||
            preg_match(DbDateValidator::REGEX_DBFORMAT_DATETIME, $requestParams[$formName]['start_date']) ||
            preg_match(DbDateValidator::REGEX_DBFORMAT_DATE, $requestParams[$formName]['end_date']) ||
            preg_match(DbDateValidator::REGEX_DBFORMAT_DATETIME, $requestParams[$formName]['end_date'])) {
            return $requestParams;
        }
        throw new HttpException(400, 'Wrong calendar entry date format.');
    }
}