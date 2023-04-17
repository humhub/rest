<?php

use humhub\modules\rest\Module;
use yii\db\Migration;

class m230401_174208_add_allow_jwt_auth extends Migration
{
    public function safeUp()
    {
        /** @var Module $module */
        $module = Yii::$app->getModule('rest');

        $module->settings->set('enableJwtAuth', !empty($module->settings->get('jwtKey')));
        $module->settings->set('enableBasicAuth', 1);
    }

    public function safeDown()
    {
        echo "m230401_174208_add_allow_jwt_auth cannot be reverted.\n";

        return false;
    }
}
