<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2019 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\rest\definitions;

use humhub\components\rendering\ViewPathRenderer;
use humhub\modules\activity\models\Activity;
use humhub\modules\comment\models\Comment;
use humhub\modules\like\models\Like;
use humhub\modules\post\models\Post;
use humhub\modules\space\models\Space;
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
            'source' => static::getSourceDefinitions($baseActivity->source),
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

    private function getSourceDefinitions($source)
    {
        switch (true) {
            case $source instanceof Space :
                return SpaceDefinitions::getSpaceShort($source);
            case $source instanceof Post :
                return PostDefinitions::getPost($source);
            case $source instanceof Comment :
                return CommentDefinitions::getComment($source);
            case $source instanceof Like :
                return LikeDefinitions::getLike($source);
        }

        return get_class($source) . ' definitions are not yet supported.';
    }
}