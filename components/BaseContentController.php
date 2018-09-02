<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\components;

use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\components\ContentContainerActiveRecord;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\definitions\ContentDefinitions;

use humhub\modules\content\components\ContentActiveRecord;
use Yii;


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

}