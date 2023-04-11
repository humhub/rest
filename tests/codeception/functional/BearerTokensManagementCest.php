<?php

namespace rest\functional;


use rest\FunctionalTester;

class BearerTokensManagementCest
{
    public function test(FunctionalTester $I)
    {
        $I->wantTo('Check Access Tokens');
        $I->amAdmin();
//        $I->amOnPage("/user/auth/login");
    }
}