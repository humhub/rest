<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace rest\acceptance;

use rest\AcceptanceTester;
use Yii;

class BearerTokensManagementCest
{
    
    public function addNewBearerToken(AcceptanceTester $I)
    {
        $expiration = (new \Datetime('tomorrow'));

        $I->amAdmin();
        $I->wantToTest('Add new access token');
        $I->amOnPage('/rest/admin/bearer-auth');
        $I->selectUserFromPicker('#restuserbearertoken-userguid', 'Peter Tester');
        $I->fillField('RestUserBearerToken[expiration]', Yii::$app->formatter->asDate($expiration, 'short'));
        $I->fillField('RestUserBearerToken[expirationTime]', Yii::$app->formatter->asTime($expiration, 'short'));
        $I->click('Add');
        $I->waitForText(Yii::$app->formatter->asDate($expiration));
    }

    public function revokeBearerToken(AcceptanceTester $I)
    {
        $I->amAdmin();
        $I->amOnPage('/rest/admin/bearer-auth');
        $I->wantToTest('Revoke access token');
        $I->click('.fa-trash');
        $I->waitForText('No results found.');
    }
}
