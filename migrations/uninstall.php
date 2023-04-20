<?php

use yii\db\Migration;

class uninstall extends Migration
{

    public function up()
    {
        $this->dropTable('impersonate_auth_tokens');
        $this->dropTable('rest_user_bearer_tokens');
    }

    public function down()
    {
        echo "uninstall does not support migration down.\n";
        return false;
    }

}
