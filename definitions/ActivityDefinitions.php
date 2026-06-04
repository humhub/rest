<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\modules\activity\models\Activity;
use humhub\modules\activity\services\RenderService;
use Yii;
use yii\base\Exception;

class ActivityDefinitions
{
    public static function getActivity(Activity $activity)
    {
        try {
            return [
                'id' => $activity->id,
                'class' => $activity->class,
                'content' => static::getActivityContent($activity),
                'originator' => UserDefinitions::getUserShort($activity->createdBy),
                'source' => SourceDefinitions::getSource($activity->content?->getPolymorphicRelation()),
                'createdAt' => $activity->created_at,
            ];
        } catch (Exception $exception) {
            Yii::error('Could not get activity. ' . $exception->getMessage(), 'api');
            return null;
        }
    }

    private static function getActivityContent($activity)
    {
        return $activity->content ? [
            'id' => $activity->content->id,
            'guid' => $activity->content->guid,
            'pinned' => (bool) $activity->content->pinned,
            'archived' => (bool) $activity->content->archived,
            'output' => (new RenderService($activity))->getWeb(),
        ] : [];
    }
}
