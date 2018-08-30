<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\content;

use humhub\modules\rest\components\BaseController;
use humhub\modules\rest\definitions\ContentDefinitions;
use humhub\modules\content\models\ContentContainer;


class ContainerController extends BaseController
{

    public function actionList()
    {
        $results = [];
        $query = ContentContainer::find();
        $this->handlePagination($query);

        foreach ($query->all() as $content) {
            $results[] = ContentDefinitions::getContentContainer($content);
        }

        return $this->returnPagination($query, $results);
    }



}