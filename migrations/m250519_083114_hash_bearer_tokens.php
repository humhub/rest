<?php

use humhub\modules\rest\models\RestUserBearerToken;
use yii\db\Migration;

class m250519_083114_hash_bearer_tokens extends Migration
{
    public function safeUp()
    {
        foreach (RestUserBearerToken::find()->each() as $token) {
            $token->token = hash('sha256', (string) $token->token);
            $token->save();
        }
    }

    public function safeDown()
    {
        echo "m250519_083114_hash_bearer_tokens cannot be reverted.\n";

        return false;
    }
}
