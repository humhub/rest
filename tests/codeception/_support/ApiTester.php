<?php
namespace rest;

use humhub\modules\rest\definitions\UserDefinitions;
use humhub\modules\user\models\User;

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

    public function getUserDefinition($username)
    {
        $user = User::findOne(['username' => $username]);
        return ($user ? UserDefinitions::getUser($user) : []);
    }

    public function seeUserDefinition($username)
    {
        $this->seeSuccessResponseContainsJson($this->getUserDefinition($username));
    }
}
