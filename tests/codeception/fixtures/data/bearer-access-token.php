<?php

use yii\db\Expression;

return [
    ['id' => '1', 'user_id' => '2', 'token' => '20af44ac87dbf8892830bb4144138e2bea1c0d1cef2701bcd46e65e23d3547c1', 'expiration' => new Expression('DATE_ADD(NOW(), INTERVAL 1 DAY)')],
];
