<?php

namespace humhub\modules\rest\components\behaviors;

use Yii;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yii\web\Controller;

class LanguagePickerBehavior extends Behavior
{
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'pickLanguage',
        ];
    }

    public function pickLanguage()
    {
        if (
            empty(Yii::$app->request->acceptableLanguages) &&
            $userLanguage = ArrayHelper::getValue(Yii::$app->user->identity, 'language')
        ) {
            Yii::$app->language = $userLanguage;
        }
    }
}