<?php
use yii\db\Schema;
use yii\db\Migration;

class m160309_181254_create_api_user extends Migration
{

    public function up()
    {
         $this->createTable('api_user', [
             'id' => 'pk',
             'client' => 'varchar(255) NOT NULL',
             'api_key' => 'varchar(32) NOT NULL',
             'active' => 'integer(1) NOT NULL',
            ],
        '');
    }

}
