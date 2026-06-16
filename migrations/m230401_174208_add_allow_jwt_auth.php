<?php

use humhub\modules\rest\Module;
use yii\db\Migration;

class m230401_174208_add_allow_jwt_auth extends Migration
{
    public function safeUp()
    {
        $jwtKey = $this->db->createCommand(
            'SELECT value FROM setting WHERE module_id = :m AND name = :n',
            [':m' => 'rest', ':n' => 'jwtKey'],
        )->queryScalar();

        $this->upsert(
            'setting',
            ['module_id' => 'rest', 'name' => 'enableJwtAuth', 'value' => !empty($jwtKey) ? '1' : '0'],
            ['value' => !empty($jwtKey) ? '1' : '0'],
        );
        $this->upsert(
            'setting',
            ['module_id' => 'rest', 'name' => 'enableBasicAuth', 'value' => '1'],
            ['value' => '1'],
        );
    }

    public function safeDown()
    {
        echo "m230401_174208_add_allow_jwt_auth cannot be reverted.\n";

        return false;
    }
}
