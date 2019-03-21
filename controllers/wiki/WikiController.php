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
    public static $moduleId = 'Wiki';

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