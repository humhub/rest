<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\wiki;

use humhub\modules\content\components\ContentActiveRecord;
use humhub\modules\rest\components\BaseContentController;
use humhub\modules\rest\definitions\WikiDefinitions;
use humhub\modules\wiki\models\WikiPage;


class WikiController extends BaseContentController
{
    public function actionIndex()
    {
        return $this->returnError(404, 'Wiki module does not installed. Please install or enable Wiki module to use this API');
    }

    /**
     * {@inheritdoc}
     */
    public function getContentActiveRecordClass()
    {
        return WikiPage::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentDefinition(ContentActiveRecord $contentRecord)
    {
        /** @var WikiPage $contentRecord */
        return WikiDefinitions::getWikiPage($contentRecord);
    }

}