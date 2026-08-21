<?php

namespace rest\api;

use humhub\models\RecordMap;
use humhub\modules\post\models\Post;
use PHPUnit\Framework\Assert;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;
use Yii;

/**
 * Tests for the island-shape like endpoints (`like/info`, `POST like`, `DELETE like`,
 * `like/user-list`), see the corresponding actions in `controllers/like/LikeController.php`.
 * Fixture baseline: content 1 (Admin's private profile post) is liked by users 3 and 4;
 * post 10 is a public post in Space 2 (guest-visible space).
 */
class LikeStateCest extends HumHubApiTestCest
{
    /**
     * @var string bearer access token of User1 (BearerAccessTokenFixture)
     */
    private const USER1_BEARER_TOKEN = '_sB714dci3pUh6FZw5BFA0wB2ri5TfQ-dxs32iaK920BI1eHn7SX0UphARYr4J-duJbF-ZuULdjOuqc1DSH3DB';

    public function testInfo(ApiTester $I)
    {
        $I->wantTo('read the like state of a record');
        $I->amAdmin();

        $recordId = $this->getPostRecordId(1);

        $I->sendGet('like/info', ['recordId' => $recordId]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['currentUserLiked' => false, 'likeCounter' => 2]);

        $I->sendGet('like/info', ['recordId' => 9999]);
        $I->seeResponseCodeIs(404);

        // Content 1 is Admin's private profile post — not visible to User1.
        // (Bearer token instead of a second basic-auth identity: switching the basic-auth
        // user mid-test breaks on the authclient collection's per-process login cache.)
        $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
        $I->sendGet('like/info', ['recordId' => $recordId]);
        $I->seeResponseCodeIs(403);
    }

    public function testLikeToggle(ApiTester $I)
    {
        $I->wantTo('like and unlike a record');
        $I->amAdmin();

        $recordId = $this->getPostRecordId(1);

        $I->sendPost("like?recordId=$recordId");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['currentUserLiked' => true, 'likeCounter' => 3]);

        $I->sendDelete("like?recordId=$recordId");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['currentUserLiked' => false, 'likeCounter' => 2]);

        // Unlike is idempotent, like the core action
        $I->sendDelete("like?recordId=$recordId");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['currentUserLiked' => false, 'likeCounter' => 2]);
    }

    public function testUserList(ApiTester $I)
    {
        $I->wantTo('page through the users who liked a record');
        $I->amAdmin();

        $recordId = $this->getPostRecordId(1);

        // Newest like first: user 4 (Andreas), then user 3 (Sara)
        $I->sendGet('like/user-list', ['recordId' => $recordId]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['total' => 2, 'hasMore' => false, 'nextPage' => null]);
        Assert::assertEquals(
            ['Andreas Tester', 'Sara Tester'],
            $I->grabDataFromResponseByJsonPath('$.users[*].displayName'),
        );
        // UserJsonService user shape
        $I->seeResponseContainsJson(['users' => [['guid' => '01e50e0d-82cd-41fc-8b0c-552392f5839f', 'contentContainerId' => 8]]]);
        Assert::assertNotEmpty($I->grabDataFromResponseByJsonPath('$.users[0].imageUrl')[0]);
        Assert::assertNotEmpty($I->grabDataFromResponseByJsonPath('$.users[0].url')[0]);

        // `limit` is clamped to [1, userListPaginationSize]: 0 becomes 1 instead of "no LIMIT"
        $I->sendGet('like/user-list', ['recordId' => $recordId, 'limit' => 0]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['total' => 2, 'hasMore' => true, 'nextPage' => 2]);
        Assert::assertEquals(['Andreas Tester'], $I->grabDataFromResponseByJsonPath('$.users[*].displayName'));

        $I->sendGet('like/user-list', ['recordId' => $recordId, 'limit' => 0, 'page' => 2]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['hasMore' => false, 'nextPage' => null]);
        Assert::assertEquals(['Sara Tester'], $I->grabDataFromResponseByJsonPath('$.users[*].displayName'));

        // Oversized limits are clamped to the module default, not passed through
        $I->sendGet('like/user-list', ['recordId' => $recordId, 'limit' => 999]);
        $I->seeResponseCodeIs(200);
        Assert::assertCount(2, $I->grabDataFromResponseByJsonPath('$.users[*].displayName'));
    }

    public function testSessionAndTokenAuth(ApiTester $I)
    {
        $I->wantTo('use the like endpoints with both session and token auth');

        $recordId = $this->getPostRecordId(10);

        // Session auth defaults OFF (see ConfigureForm); enable it for the session portion and
        // restore the default so it does not leak to other cests on the shared DB.
        $settings = Yii::$app->getModule('rest')->settings;
        $settings->set('enableSessionAuth', true);

        try {
            // Session auth (User1 = id 2). amLoggedInAs() must run before the first API request
            // of the test: the first request replaces the app's user component with the
            // session-less API one, after which the Yii2 module can no longer seed a session.
            $I->amLoggedInAs(2);
            $I->sendGet('like/info', ['recordId' => $recordId]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['currentUserLiked' => false, 'likeCounter' => 0]);

            // Session-authenticated mutation requires the CSRF token
            $I->sendPost("like?recordId=$recordId");
            $I->seeResponseCodeIs(403);

            $rawToken = Yii::$app->security->generateRandomString();
            $I->setCookie('_csrf', $rawToken);
            $I->haveHttpHeader('X-CSRF-Token', Yii::$app->security->maskToken($rawToken));
            $I->sendPost("like?recordId=$recordId");
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['currentUserLiked' => true, 'likeCounter' => 1]);

            // Token (bearer) auth — takes precedence over the still-present session (same user here)
            $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
            $I->sendGet('like/info', ['recordId' => $recordId]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['currentUserLiked' => true, 'likeCounter' => 1]);
        } finally {
            $settings->set('enableSessionAuth', false);
        }
    }

    public function testGuestAccess(ApiTester $I)
    {
        $I->wantTo('see guest access to like info mirror the core controller');

        $recordId = $this->getPostRecordId(10);

        // Guest access disabled (default): 401 like every other API request
        $I->sendGet('like/info', ['recordId' => $recordId]);
        $I->seeResponseCodeIs(401);

        Yii::$app->getModule('user')->settings->set('auth.allowGuestAccess', 1);

        // `info` is guest-allowed on guest-visible content, like in the core controller
        $I->sendGet('like/info', ['recordId' => $recordId]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['currentUserLiked' => false, 'likeCounter' => 0]);

        // Content that is not guest-visible stays denied
        $I->sendGet('like/info', ['recordId' => $this->getPostRecordId(1)]);
        $I->seeResponseCodeIs(403);

        // Everything else stays logged-in only, mirroring core's guestAllowedActions = ['info']
        $I->sendGet('like/user-list', ['recordId' => $recordId]);
        $I->seeResponseCodeIs(401);
        $I->sendPost("like?recordId=$recordId");
        $I->seeResponseCodeIs(401);
        $I->sendDelete("like?recordId=$recordId");
        $I->seeResponseCodeIs(401);
    }

    /**
     * Returns the RecordMap id of the given post — what the like island passes as `recordId`.
     */
    private function getPostRecordId(int $postId): int
    {
        return RecordMap::getId(Post::findOne(['id' => $postId]));
    }
}
