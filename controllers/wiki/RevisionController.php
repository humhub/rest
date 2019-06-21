<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\wiki;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\WikiDefinitions;
use humhub\modules\wiki\models\WikiPage;
use humhub\modules\wiki\models\WikiPageRevision;

class RevisionController extends BaseController
{
    public function actionIndex($pageId)
    {
        $page = WikiPage::findOne(['id' => $pageId]);
        if (! $page) {
            return $this->returnError(404, 'Page not found!');
        }

        $results = [];
        $query = $page->getRevisions();

        $pagination = $this->handlePagination($query);
        foreach ($query->all() as $revision) {
            $results[] = WikiDefinitions::getWikiPageRevision($revision);
        }
        return $this->returnPagination($query, $pagination, $results);
    }

    public function actionView($id)
    {
        $revision = WikiPageRevision::findOne(['id' => $id]);

        if ($revision === null) {
            return $this->returnError(404, 'Wiki page revision not found!');
        }

        return WikiDefinitions::getWikiPageRevision($revision);
    }

    public function actionRevert($id)
    {
        $revision = WikiPageRevision::findOne(['id' => $id]);
        if ($revision === null) {
            return $this->returnError(404, 'Wiki page revision not found!');
        }
        if ($revision->is_latest) {
            return $this->returnError(400, 'Revert not possible. Already latest revision!');
        }

        /** @var WikiPage $page */
        $page = $revision->page;
        if (! $page) {
            return $this->returnError(404, 'Target wiki page not found!');
        }
        if (!$page->content->canEdit()) {
            return $this->returnError(403, 'Page not editable!');
        }

        $revertedRevision = $page->createRevision();
        $revertedRevision->content = $revision->content;

        if ($revertedRevision->save()) {
            return $this->returnSuccess('Wiki page revision successfully reverted.');
        } else {
            return $this->returnError(500, 'Internal error while revert wiki page!');
        }
    }
}