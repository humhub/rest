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
    
    public function test(AcceptanceTester $I)
    {
        $I->amAdmin();

        $I->wantToTest('Check bearer access tokens');
        $I->amGoingTo('Add new token');

        $I->amOnPage('/rest/admin/bearer-auth');
        $I->selectUserFromPicker('#restuserbearertoken-userguid', 'Peter Tester');
//        $I->fillField('#contentFormBody .humhub-ui-richtext[contenteditable]', $postContent);

//        $I->click('');

    }
}