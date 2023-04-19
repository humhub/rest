<?php

use yii\db\Migration;

class m230416_173326_impersonate_auth extends Migration
{
    public function safeUp()
    {
        $this->createTable('impersonate_auth_tokens', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token' => $this->string(86)->notNull(),
            'expiration' => $this->timestamp()->notNull(),
        ]);

        $this->addForeignKey(
            'impersonate_auth_tokens_to_user',
            'impersonate_auth_tokens',
            'user_id',
            'user',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        echo "m230416_173326_impersonate_auth cannot be reverted.\n";

        return false;
    }
}
