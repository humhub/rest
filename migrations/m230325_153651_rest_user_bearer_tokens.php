<?php

use yii\db\Migration;

class m230325_153651_rest_user_bearer_tokens extends Migration
{
    public function safeUp()
    {
        $this->createTable('rest_user_bearer_tokens', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'token' => $this->string(86)->notNull(),
            'expiration' => $this->timestamp()->notNull(),
        ]);

        $this->addForeignKey(
            'rest_user_bearer_tokens_to_user',
            'rest_user_bearer_tokens',
            'user_id',
            'user',
            'id',
            'CASCADE',
            'CASCADE',
        );
    }

    public function safeDown()
    {
        echo "m230325_153651_rest_user_bearer_tokens cannot be reverted.\n";

        return false;
    }
}
