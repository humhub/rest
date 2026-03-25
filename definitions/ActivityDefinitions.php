<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\activity\models\Activity;
use humhub\modules\activity\services\RenderService;

class ActivityDefinitions
{
    public static function getActivity(Activity $activity)
    {
        return [
            'id' => $activity->id,
            'class' => $activity->class,
            'content' => static::getActivityContent($activity),
            'originator' => UserDefinitions::getUserShort($activity->createdBy),
            'source' => SourceDefinitions::getSource($activity->content->getPolymorphicRelation()),
            'createdAt' => $activity->content->created_at,
        ];
    }

    private static function getActivityContent($activity)
    {
        return [
            'id' => $activity->content->id,
            'guid' => $activity->content->guid,
            'pinned' => (bool) $activity->content->pinned,
            'archived' => (bool) $activity->content->archived,
            'output' => (new RenderService($activity))->getWeb(),
        ];
    }
}
