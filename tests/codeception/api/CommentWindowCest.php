<?php

namespace rest\api;

use humhub\modules\comment\notifications\CommentDeleted;
use humhub\modules\notification\models\Notification;
use PHPUnit\Framework\Assert;
use rest\ApiTester;
use tests\codeception\_support\HumHubApiTestCest;
use Yii;

/**
 * Tests for the island-shape comment endpoints (`comment/window`, `comment/<id>/full`),
 * see `controllers/comment/WindowController.php`. Fixture baseline: comment 1 by Admin
 * on content 1 (Admin's private profile post); content 10 is a public post in Space 2
 * (guest-visible space, User1 is a member).
 */
class CommentWindowCest extends HumHubApiTestCest
{
    /**
     * @var string bearer access token of User1 (BearerAccessTokenFixture)
     */
    private const USER1_BEARER_TOKEN = '_sB714dci3pUh6FZw5BFA0wB2ri5TfQ-dxs32iaK920BI1eHn7SX0UphARYr4J-duJbF-ZuULdjOuqc1DSH3DB';

    public function testWindowPagination(ApiTester $I)
    {
        $I->wantTo('page through a comment window with roots and replies');
        $I->amAdmin();

        // Fixture comment 1 is the oldest root; add three roots and two replies on root 2
        $root2 = $this->createComment($I, ['message' => 'Root 2', 'contentId' => 1]);
        $root3 = $this->createComment($I, ['message' => 'Root 3', 'contentId' => 1]);
        $root4 = $this->createComment($I, ['message' => 'Root 4', 'contentId' => 1]);
        $this->createComment($I, ['message' => 'Reply 1', 'contentId' => 1, 'parentCommentId' => $root2]);
        $this->createComment($I, ['message' => 'Reply 2', 'contentId' => 1, 'parentCommentId' => $root2]);

        // Initial window: the newest `commentsPreviewMax` (2) roots, ascending order
        $I->sendGet('comment/window', ['contentId' => 1]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'prevCount' => 2,
            'nextCount' => 0,
            'total' => 6, // all comments including replies
            'rootTotal' => 4, // root comments only
        ]);
        $ids = $I->grabDataFromResponseByJsonPath('$.comments[*].id');
        Assert::assertEquals([$root3, $root4], $ids);

        // "Show previous" from root 3: both remaining older roots fit the single-overflow
        // rule (limit 1 + exactly one more), so the window returns them both
        $I->sendGet('comment/window', [
            'contentId' => 1,
            'commentId' => $root3,
            'direction' => 'previous',
            'pageSize' => 1,
        ]);
        $I->seeResponseCodeIs(200);
        $ids = $I->grabDataFromResponseByJsonPath('$.comments[*].id');
        Assert::assertEquals([1, $root2], $ids);
        $I->seeResponseContainsJson(['prevCount' => 0, 'nextCount' => 2]);

        // Page size clamp: 0 is clamped to 1, not passed through (which would drop the LIMIT)
        $I->sendGet('comment/window', [
            'contentId' => 1,
            'commentId' => $root4,
            'direction' => 'previous',
            'pageSize' => 0,
        ]);
        $I->seeResponseCodeIs(200);
        $ids = $I->grabDataFromResponseByJsonPath('$.comments[*].id');
        Assert::assertEquals([$root3], $ids);

        // Reply window of root 2
        $I->sendGet('comment/window', ['contentId' => 1, 'parentCommentId' => $root2]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['total' => 2, 'rootTotal' => 4]);
        $messages = $I->grabDataFromResponseByJsonPath('$.comments[*].message');
        Assert::assertEquals(['Reply 1', 'Reply 2'], $messages);

        // Child preview embedded in the root's own island shape
        $I->sendGet("comment/$root2/full");
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['children' => ['total' => 2, 'hasMore' => false]]);
        Assert::assertCount(2, $I->grabDataFromResponseByJsonPath('$.children.items[*].id'));
    }

    public function testViewerContextFields(ApiTester $I)
    {
        $I->wantTo('see viewer-context fields in the island comment shape');
        $I->amAdmin();

        $I->sendGet('comment/1/full');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson([
            'id' => 1,
            'contentId' => 1,
            'parentCommentId' => null,
            'message' => 'Comment 1 of the Post 1',
            'isEdited' => false,
            'blocked' => false,
            'canEdit' => true,
            'canDelete' => true,
            'canAdminDelete' => false, // own comment
            'likes' => ['count' => 0, 'liked' => false],
            'author' => [
                'guid' => '01e50e0d-82cd-41fc-8b0c-552392f5839c',
                'displayName' => 'Admin Tester',
                'contentContainerId' => 1,
                'online' => null, // viewer looks at themself
            ],
        ]);
        Assert::assertNotEmpty($I->grabDataFromResponseByJsonPath('$.permalink')[0]);
        Assert::assertNotEmpty($I->grabDataFromResponseByJsonPath('$.author.imageUrl')[0]);
        Assert::assertNotNull($I->grabDataFromResponseByJsonPath('$.messageRenderOptions')[0]);

        $I->sendGet('comment/999/full');
        $I->seeResponseCodeIs(404);

        // Content 1 is Admin's private profile post — not visible to User1.
        // (Bearer token instead of a second basic-auth identity: switching the basic-auth
        // user mid-test breaks on the authclient collection's per-process login cache.)
        $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
        $I->sendGet('comment/1/full');
        $I->seeResponseCodeIs(403);
        $I->sendGet('comment/window', ['contentId' => 1]);
        $I->seeResponseCodeIs(403);
    }

    public function testCreateValidation(ApiTester $I)
    {
        $I->wantTo('see the 422 validation contract on comment creation');
        $I->amAdmin();

        $I->sendPost('comment/full', ['contentId' => 1]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['errors' => ['message' => ['The comment must not be empty!']]]);

        // One nesting level only
        $root = $this->createComment($I, ['message' => 'Root', 'contentId' => 1]);
        $reply = $this->createComment($I, ['message' => 'Reply', 'contentId' => 1, 'parentCommentId' => $root]);
        $I->sendPost('comment/full', ['message' => 'Nested', 'contentId' => 1, 'parentCommentId' => $reply]);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['errors' => ['parentCommentId' => ['Comments can only be nested one level deep.']]]);

        $I->sendPost('comment/full', ['message' => 'No such content', 'contentId' => 9999]);
        $I->seeResponseCodeIs(404);
    }

    public function testUpdateAndEditorFetch(ApiTester $I)
    {
        $I->wantTo('update a comment and fetch its raw message for the editor');
        $I->amAdmin();

        $id = $this->createComment($I, ['message' => 'Original', 'contentId' => 1]);

        $I->sendPut("comment/$id/full", ['message' => 'Edited message']);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['id' => $id, 'message' => 'Edited message']);

        $I->sendGet("comment/$id/full/edit");
        $I->seeResponseCodeIs(200);
        $I->seeResponseEquals(json_encode(['message' => 'Edited message']));

        $I->sendPut("comment/$id/full", ['message' => '']);
        $I->seeResponseCodeIs(422);
        $I->seeResponseContainsJson(['errors' => ['message' => ['The comment must not be empty!']]]);

        // Not the author (and no permission on the content at all)
        $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
        $I->sendPut('comment/1/full', ['message' => 'Hijack']);
        $I->seeResponseCodeIs(403);
    }

    public function testDelete(ApiTester $I)
    {
        $I->wantTo('delete comments including the admin notify flow');

        // User1 (bearer token) comments on a public space post, Admin removes it with a notification
        $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
        $userCommentId = $this->createComment($I, ['message' => 'To be moderated', 'contentId' => 10]);

        $I->deleteHeader('Authorization');
        $I->amAdmin();
        $I->sendDelete("comment/$userCommentId/full", [
            'AdminDeleteCommentForm' => ['notify' => 1, 'message' => 'Against the rules'],
        ]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => 1]);
        $I->seeRecord(Notification::class, ['class' => CommentDeleted::class, 'user_id' => 2]);
        $I->sendGet("comment/$userCommentId/full");
        $I->seeResponseCodeIs(404);

        // No delete permission for User1 on Admin's comment (content not even visible);
        // the bearer token takes precedence over the still-configured basic auth
        $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
        $I->sendDelete('comment/1/full');
        $I->seeResponseCodeIs(403);

        // Plain delete of the own fixture comment
        $I->deleteHeader('Authorization');
        $I->sendDelete('comment/1/full');
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['success' => 1]);
        $I->sendGet('comment/1/full');
        $I->seeResponseCodeIs(404);
    }

    public function testDeleteWithoutPermission(ApiTester $I)
    {
        $I->wantTo('be rejected when deleting a comment without permission');
        $I->amUser1();

        $I->sendDelete('comment/1/full');
        $I->seeResponseCodeIs(403);
    }

    public function testSessionAndTokenAuth(ApiTester $I)
    {
        $I->wantTo('use the window endpoints with both session and token auth');

        // Session auth defaults OFF (see ConfigureForm); enable it for the session portion and
        // restore the default so it does not leak to other cests on the shared DB.
        $settings = Yii::$app->getModule('rest')->settings;
        $settings->set('enableSessionAuth', true);

        try {
            // Session auth (User1 = id 2). amLoggedInAs() must run before the first API request
            // of the test: the first request replaces the app's user component with the
            // session-less API one, after which the Yii2 module can no longer seed a session.
            $I->amLoggedInAs(2);
            $I->sendGet('comment/window', ['contentId' => 10]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['total' => 0, 'rootTotal' => 0]);

            // Session-authenticated mutation requires the CSRF token
            $I->sendPost('comment/full', ['message' => 'No CSRF', 'contentId' => 10]);
            $I->seeResponseCodeIs(403);

            $rawToken = Yii::$app->security->generateRandomString();
            $I->setCookie('_csrf', $rawToken);
            $I->haveHttpHeader('X-CSRF-Token', Yii::$app->security->maskToken($rawToken));
            $I->sendPost('comment/full', ['message' => 'With CSRF', 'contentId' => 10]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['message' => 'With CSRF', 'canEdit' => true]);

            // Token (bearer) auth — takes precedence over the still-present session (same user
            // here) and sees the comment created above
            $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
            $I->sendGet('comment/window', ['contentId' => 10]);
            $I->seeResponseCodeIs(200);
            $I->seeResponseContainsJson(['total' => 1, 'rootTotal' => 1]);
        } finally {
            $settings->set('enableSessionAuth', false);
        }
    }

    public function testGuestAccess(ApiTester $I)
    {
        $I->wantTo('see guest access to comment windows mirror the core controller');

        // Guest-visible baseline data: a comment on the public post in the guest-visible Space 2.
        // Created via bearer token — basic-auth credentials could not be fully cleared again
        // for the guest requests below (they are server params, not a header).
        $I->amBearerAuthenticated(self::USER1_BEARER_TOKEN);
        $this->createComment($I, ['message' => 'Public comment', 'contentId' => 10]);
        $I->deleteHeader('Authorization');

        // Guest access disabled (default): 401 like every other API request
        $I->sendGet('comment/window', ['contentId' => 10]);
        $I->seeResponseCodeIs(401);

        Yii::$app->getModule('user')->settings->set('auth.allowGuestAccess', 1);

        $I->sendGet('comment/window', ['contentId' => 10]);
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['total' => 1, 'rootTotal' => 1]);
        $I->seeResponseContainsJson(['comments' => [['message' => 'Public comment']]]);

        // guestHideComments rejects guests with 403 (enforced by CommentJsonService)
        $commentModule = Yii::$app->getModule('comment');
        $commentModule->guestHideComments = true;
        try {
            $I->sendGet('comment/window', ['contentId' => 10]);
            $I->seeResponseCodeIs(403);
        } finally {
            $commentModule->guestHideComments = false;
        }

        // Content that is not guest-visible stays 403 even with guest access enabled
        $I->sendGet('comment/window', ['contentId' => 1]);
        $I->seeResponseCodeIs(403);

        // Mutations are never guest-accessible
        $I->sendPost('comment/full', ['message' => 'Guest comment', 'contentId' => 10]);
        $I->seeResponseCodeIs(401);
    }

    /**
     * Creates a comment through the island-shape endpoint and returns its id.
     */
    private function createComment(ApiTester $I, array $params): int
    {
        $I->sendPost('comment/full', $params);
        $I->seeResponseCodeIs(200);

        return (int)$I->grabDataFromResponseByJsonPath('$.id')[0];
    }
}
