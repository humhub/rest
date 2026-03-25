<?php

namespace rest;

use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\user\models\User;
use Codeception\Util\HttpCode;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
*/
class ApiTester extends \ApiTester
{
    use _generated\ApiTesterActions;

    /**
     * Define custom actions here
     */

    public function getUserDefinition(string $username): array
    {
        $user = User::findOne(['username' => $username]);
        return ($user ? UserDefinitions::getUser($user) : []);
    }

    public function getUserDefinitions(array $usernames, string $type = 'full'): array
    {
        $users = User::find()->where(['IN', 'username', $usernames])->all();
        $userDefinitions = [];
        foreach ($users as $user) {
            $userDefinitions[] = $type == 'short' ? UserDefinitions::getUserShort($user) : UserDefinitions::getUser($user);
        }
        return $userDefinitions;
    }

    public function seeValidationMessage($message)
    {
        $this->seeCodeResponseContainsJson(HttpCode::UNPROCESSABLE_ENTITY, ['message' => $message]);
    }
}
