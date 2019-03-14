<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2018 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\controllers\space;

use yii\db\conditions\OrCondition;

use humhub\modules\rest\definitions\SpaceDefinitions;
use humhub\modules\rest\controllers\content\ContainerController;
use humhub\modules\rest\definitions\WikiDefinitions;
use humhub\modules\space\models\Space;

class SpaceController extends ContainerController
{
    /**
     * {@inheritdoc}
     */
    public function getContentContainerActiveRecordClass()
    {
        return Space::class;
    }

    /**
     * {@inheritdoc}
     */
    public function returnContentContainerDefinition($contentcontainerIds, $searchQueryParam)
    {
        $result = [];
        $query = Space::find()
            -> where(['contentcontainer_id' => $contentcontainerIds]);

        if ($searchQueryParam != null){
             $query -> andwhere(new OrCondition([
               ['like', 'name', $searchQueryParam],
               ['like', 'description', $searchQueryParam],
               ['like', 'tags', $searchQueryParam],
            ]));

        }

        foreach($query -> all() as $item){
            $result[] =  SpaceDefinitions::getSpace($item);
        }
        return $result;
    }
}