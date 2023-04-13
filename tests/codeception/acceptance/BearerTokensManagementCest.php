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
//        $I->enableModule(1, 'rest');

//        $I->wantToTest('the the shifting of a meeting item');
//        $I->amGoingTo('submit a the meeting form');
//
//        $I->amOnSpace(1, '/meeting/index');
//        $I->see('New meeting');
//        $I->click('New meeting');
//
//        $I->waitForText('Create new meeting');
//
//        $I->fillField('Meeting[title]', 'Test Meeting 1');
//
//        $I->click('#meetingform-startdate');
//        $I->wait(1);
//        $I->click('.ui-datepicker-today', '#ui-datepicker-div');
//
//        $I->fillField('MeetingForm[startTime]', '12:00 PM');
//        $I->fillField('MeetingForm[endTime]', '1:00 PM');
//
//
//        $I->fillField('Meeting[location]', 'Test Location');
//        $I->fillField('Meeting[room]', 'Test Room');
//
//        $I->selectUserFromPicker('#participantPicker', 'Sara Tester');
//
//        $I->click('#external-participants-link');
//        $I->wait(1);
//        $I->fillField('Meeting[external_participants]', 'Sonja Soja');
//
//        $I->click('Save', '#globalModal');
//        $I->waitForElementVisible('#meeting-agenda-create');
//        $I->click('#meeting-agenda-create');
//
//        $I->waitForText('Create new entry', null, '#globalModal');
//
//        $I->fillField('MeetingItem[title]', 'AgendaEntry1');
//        $I->fillField('MeetingItemForm[duration]', '1:00');
//        $I->click('[type="submit"]', '#globalModal');
//
//        $I->wait(2);
//
//        $I->click('#meeting-agenda-create');
//
//        $I->waitForText('Create new entry',null, '#globalModal');
//
//        $I->fillField('MeetingItem[title]', 'AgendaEntry2');
//        $I->fillField('MeetingItemForm[duration]', '1:00');
//        $I->click('[type="submit"]', '#globalModal');
//
//        $I->wait(2);
//
//        $I->jsClick('.fa-exchange:first');
//
//        $I->waitForText('Shift agenda item', null, '#globalModal');
//        $I->click('Create new meeting', '#globalModal');
//
//        $I->waitForText('Shift agenda entry to new meeting', null, '#globalModal');
//        $I->fillField('Meeting[title]', 'New Meeting');
//        $I->click('[type="submit"]', '#globalModal');
//
//        $I->waitForText('New Meeting', 10, '#meeting-container');
    }
}