<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\components\rendering\ViewPathRenderer;
use humhub\modules\activity\models\Activity;
use Yii;
use yii\base\Exception;

class ActivityDefinitions
{
    public static function getActivity(Activity $activity)
    {
        $baseActivity = $activity->getActivityBaseClass();

        return [
            'id' => $activity->id,
            'class' => $activity->class,
            'content' => static::getActivityContent($activity, $baseActivity),
            'originator' => UserDefinitions::getUserShort($baseActivity->originator),
            'source' => SourceDefinitions::getSource($baseActivity->source),
            'createdAt' => $activity->content->created_at
        ];
    }

    private static function getActivityContent($activity, $baseActivity)
    {
        return [
            'id' => $activity->content->id,
            'guid' => $activity->content->guid,
            'pinned' => (boolean) $activity->content->pinned,
            'archived' => (boolean) $activity->content->archived,
            'output' => static::getActivityOutput($baseActivity),
        ];
    }
    
    private static function getActivityOutput($baseActivity)
    {
        try {
            return (new ViewPathRenderer())->renderView($baseActivity, $baseActivity->getViewParams());
        } catch (Exception $exception) {
            Yii::error('Could not render activity output. ' . $exception->getMessage(), 'api');
            return '';
        }
    }
}