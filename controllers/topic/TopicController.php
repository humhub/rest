<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\topic;

use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\TopicDefinitions;
use humhub\modules\space\models\Membership;
use humhub\modules\space\models\Space;
use humhub\modules\topic\models\Topic;
use humhub\modules\user\models\User;
use Yii;

class TopicController extends BaseController
{
    public function actionIndex()
    {
        $results = [];

        if (Yii::$app->user->isAdmin()) {
            $query = Topic::find();
        } else {
            $query = $this->getRelationTopicsQuery();
        }

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $topic) {
            $results[] = TopicDefinitions::getTopic($topic);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionFindByContainer($containerId)
    {
        $results = [];

        $query = $this->getRelationTopicsQuery($containerId);

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $topic) {
            $results[] = TopicDefinitions::getTopic($topic);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        if (Yii::$app->user->isAdmin()) {
            $topic = Topic::findOne($id);
        } else {
            $topic = $this->getRelationTopicsQuery()->andWhere(['content_tag.id' => $id])->one();
        }

        if ($topic === null) {
            return $this->returnError(404, 'Topic not found!');
        }

        return TopicDefinitions::getTopic($topic);
    }

    public function actionCreate($containerId)
    {
        $contentContainer = ContentContainer::findOne(['id' => $containerId]);
        $container = $contentContainer->getPolymorphicRelation();
        
        if ($contentContainer->class === User::class && ($contentContainer->pk !== Yii::$app->user->id && ! Yii::$app->user->isAdmin())) {
            return $this->returnError(401, 'You are not allowed to create topic for user!');
        }

        if ($contentContainer->class === Space::class && (! $container->isAdmin() && ! Yii::$app->user->isAdmin())) {
            return $this->returnError(401, 'You are not allowed to create topic for space!');
        }

        $topic = new Topic($container);
        $topic->load(Yii::$app->request->getBodyParams(), '');
        $topic->validate();

        if($topic->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                $topic->getErrors(),
            ]);
        }

        if ($topic->save()) {
            return TopicDefinitions::getTopic($topic);
        }

        Yii::error('Could not create validated topic.', 'api');
        return $this->returnError(500, 'Internal error while save topic!');
    }

    public function actionUpdate($id)
    {
        if (Yii::$app->user->isAdmin()) {
            $topic = Topic::findOne($id);
        } else {
            $topic = $this->getRelationTopicsQuery()->andWhere(['content_tag.id' => $id])->one();
        }

        if ($topic === null) {
            return $this->returnError(404, 'Topic not found!');
        }

        $container = $topic->getContainer();

        if ($container instanceof Space && (! $container->isAdmin() && ! Yii::$app->user->isAdmin())) {
            return $this->returnError(401, 'You are not allowed to manage this topic!');
        }

        $topic->load(Yii::$app->request->getBodyParams(), '');
        $topic->validate();

        if($topic->hasErrors()) {
            return $this->returnError(422, 'Validation failed', [
                $topic->getErrors(),
            ]);
        }
        if ($topic->save()) {
            return TopicDefinitions::getTopic($topic);
        }

        Yii::error('Could not update topic.', 'api');
        return $this->returnError(500, 'Internal error while update topic!');
    }

    public function actionDelete($id)
    {
        if (Yii::$app->user->isAdmin()) {
            $topic = Topic::findOne($id);
        } else {
            $topic = $this->getRelationTopicsQuery()->andWhere(['content_tag.id' => $id])->one();
        }

        if ($topic === null) {
            return $this->returnError(404, 'Topic not found!');
        }

        $container = $topic->getContainer();

        if ($container instanceof Space && (! $container->isAdmin() && ! Yii::$app->user->isAdmin())) {
            return $this->returnError(401, 'You are not allowed to delete this topic!');
        }

        if ($topic->delete()) {
            return $this->returnSuccess('Topic successfully deleted!');
        }

        Yii::error('Could not delete topic.', 'api');
        return $this->returnError(500, 'Internal error while delete topic!');
    }

    protected function getRelationTopicsQuery($contentContainerId = null)
    {
        $spaceIds = Membership::getUserSpaceIds(Yii::$app->user->id);

        $query = Topic::find()
            ->select('content_tag.*')
            ->leftJoin('contentcontainer', '`contentcontainer`.`id` = `content_tag`.`contentcontainer_id`');

        if ($contentContainerId) {
            $query = $this->prepareContainerQuery($query, $contentContainerId, $spaceIds);
        } else {
            $query->andWhere(['or',
                ['contentcontainer.pk' => Yii::$app->user->id, 'contentcontainer.class' => User::class],
                ['contentcontainer.pk' => $spaceIds, 'contentcontainer.class' => Space::class],
            ]);
        }

        return $query;
    }

    protected function prepareContainerQuery($query, $contentContainerId, $spaceIds)
    {
        $contentContainer = ContentContainer::findOne(['id' => $contentContainerId]);
        if (Yii::$app->user->isAdmin()) {
            $query->andWhere(['contentcontainer.pk' => $contentContainer->pk, 'contentcontainer.class' => $contentContainer->class]);
        } else {
            if ($contentContainer->class === User::class && $contentContainer->pk === Yii::$app->user->id) {
                $query->andWhere(['contentcontainer.pk' => $contentContainer->pk, 'contentcontainer.class' => $contentContainer->class]);
            } else {
                $query->andWhere(['and',
                    ['contentcontainer.pk' => $contentContainer->pk, 'contentcontainer.class' => $contentContainer->class],
                    ['contentcontainer.pk' => $spaceIds, 'contentcontainer.class' => Space::class]
                ]);
            }
        }

        return $query;
    }
}