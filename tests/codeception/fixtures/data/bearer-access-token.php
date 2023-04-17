<?php

use yii\db\Expression;

return [
    ['id' => '1', 'user_id' => '2', 'token' => '_sB714dci3pUh6FZw5BFA0wB2ri5TfQ-dxs32iaK920BI1eHn7SX0UphARYr4J-duJbF-ZuULdjOuqc1DSH3DB', 'expiration' => new Expression('DATE_ADD(NOW(), INTERVAL 1 DAY)')],
];
