<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\content;

use humhub\modules\activity\models\Activity;
use humhub\modules\content\components\ActiveQueryContent;
use humhub\modules\content\models\ContentContainer;
use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\content\models\Content;


class ContentController extends BaseController
{

    public function actionFindByContainer($id, $order_by = 'created_at', $date_updated_from = null, $date_updated_to = null)
    {
        $contentContainer = ContentContainer::findOne(['id' => (int) $id]);
        if ($contentContainer === null) {
            return $this->returnError(404, 'Content container not found!');
        }

        $results = [];
        $query = (new ActiveQueryContent(Content::class))
            ->leftJoin(ContentContainer::tableName(), ContentContainer::tableName() . '.id = ' . Content::tableName() . '.contentcontainer_id')
            ->where([Content::tableName() . '.contentcontainer_id' => (int) $id])
            ->andWhere(['!=', Content::tableName() . '.object_model', Activity::class])
            ->orderBy([Content::tableName() . '.' . $order_by => SORT_DESC])
            ->readable();

        if(!empty($date_updated_from)) {
            $date_updated_from = is_numeric($date_updated_from) ? (int) $date_updated_from : strtotime($date_updated_from);
            $query->andWhere([
                '>=', Content::tableName() . '.updated_at', date('Y-m-d H:i:s', $date_updated_from)
            ]);
        }

        if(!empty($date_updated_to)) {
            $date_updated_to = is_numeric($date_updated_to) ? (int) $date_updated_to : strtotime($date_updated_to);
            $query->andWhere([
                '<=', Content::tableName() . '.updated_at', date('Y-m-d H:i:s', $date_updated_to)
            ]);
        }

        // Remove "Join with Content" from \humhub\modules\content\components\ActiveQueryContent::readable(),
        // because here main table is already Content:
        foreach ($query->joinWith as $j => $joinWith) {
            if (isset($joinWith[0][0]) && $joinWith[0][0] == 'content') {
                unset($query->joinWith[$j]);
                break;
            }
        }

        $pagination = $this->handlePagination($query);

        foreach ($query->all() as $content) {
            $results[] = ContentDefinitions::getContent($content);
        }

        return $this->returnPagination($query, $pagination, $results);
    }


    public function actionView($id)
    {
        $content = Content::findOne(['id' => $id]);
        if ($content === null) {
            return $this->returnError(404, 'Content not found!');
        }
        if (!$content->canView()) {
            return $this->returnError(403, 'You cannot view this content!');
        }

        return ContentDefinitions::getContent($content);
    }


    public function actionDelete($id)
    {
        $content = Content::findOne(['id' => $id]);
        if ($content === null) {
            return $this->returnError(404, 'Content not found!');
        }
        if (!$content->canEdit()) {
            return $this->returnError(403, 'You cannot delete this content!');
        }

        if ($content->delete()) {
            return $this->returnSuccess('Content successfully deleted!');
        }
        return $this->returnError(500, 'Internal error while delete content!');
    }
}
