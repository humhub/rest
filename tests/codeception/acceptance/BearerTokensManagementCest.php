<?php
/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) 2023 HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 *
 */

namespace rest\acceptance;

use rest\AcceptanceTester;

class BearerTokensManagementCest
{
    
    public function addNewBearerToken(AcceptanceTester $I)
    {
        $I->amAdmin();

        $I->wantToTest('Check bearer access tokens');
        $I->amGoingTo('Add new access token');

        $I->amOnPage('/rest/admin/bearer-auth');
        $I->selectUserFromPicker('#restuserbearertoken-userguid', 'Peter Tester');
        $I->fillField('RestUserBearerToken[expiration]', Yii::$app->formatter->asTime((new Datetime('tomorrow')), 'short'));
        $I->click('Add');
        $I->waitForText('Saved');
    }

    public function revokeBearerToken(AcceptanceTester $I)
    {
        $I->amAdmin();

        $I->wantToTest('Check bearer access tokens');
        $I->amGoingTo('Revoke access token');
    }
}