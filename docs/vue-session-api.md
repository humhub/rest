# Session Authentication & Vue Islands Endpoint Gap Analysis

Status: working document for the `vue` branch (core `enh/vuejs-integration`).
Goal: let the Vue islands UI in core consume `/api/v1` instead of core-internal
JSON controllers.

## 1. Session authentication (implemented on this branch)

`/api/v1` now accepts the regular HumHub browser session as an authentication
method, in addition to the existing token methods.

### Auth method order (CompositeAuth, `components/BaseController.php`)

1. `JwtAuth` (if `enableJwtAuth`)
2. `HttpBearerAuth` (if `enableBearerAuth`)
3. `QueryParamAuth` (if `enableBearerAuth` + `enableQueryParamAuth`)
4. `HttpBasicAuth` (if `enableBasicAuth`)
5. `ImpersonateAuth` (always)
6. **`SessionAuth` (if `enableSessionAuth`) — always last**

**Token wins:** a request carrying a valid token is authenticated as the token
user even when a session cookie is present. Note the fall-through is not uniform:
only `JwtAuth` returns `null` on failure (falling through to session auth); the
Bearer/QueryParam/Basic/Impersonate methods throw on an invalid credential, so a
malformed token on a login-required action yields 401 rather than silently
downgrading to the session. On the guest-allowed actions (`optional` list) an
invalid token downgrades to guest. Guests without token and session get the usual
401 JSON.

### CSRF contract

- Session-authenticated **state-changing** requests (POST/PUT/PATCH/DELETE)
  require a valid Yii CSRF token: `X-CSRF-Token` header (what `humhub.client`
  sends, fed from the `csrf-token` meta tag) or `_csrf` body param.
- Missing/invalid token → **403 JSON** (`ForbiddenHttpException`), never an
  HTML error page (response format is forced to JSON for `/api/` early in
  `Events::onBeforeRequest`).
- GET/HEAD/OPTIONS are exempt. Token-authenticated requests remain CSRF-exempt
  exactly as before.
- Implementation detail: `SessionAuth` does **not** call
  `Request::validateCsrfToken()` (that method generates and Set-Cookies a fresh
  `_csrf` token when the request carries none, which would clobber the browser
  page's real token). It reads the raw token straight from the `_csrf` cookie
  (core default), unmasks the client-supplied token and compares timing-safely.
  No API response ever emits a `_csrf` Set-Cookie.

### Setting

- `enableSessionAuth` (module setting, checkbox on the admin config form,
  `ConfigureForm`). **Default: disabled**, like every other auth method — a
  module update must never silently open a new authentication surface. The
  dev/Vue-islands instance enables it explicitly in the admin config form.

### Allowlist decision

Session auth deliberately **bypasses** the "Enabled for all registered users" /
user allowlist gate (`BaseController::isUserEnabled()`):

- The gate limits who may reach the API *from outside the browser* with
  self-obtained credentials/tokens; its own admin hints say it "affects JWT and
  HTTP Basic Authentication methods only".
- A session-authenticated call grants nothing the same user's browser session
  does not already have via the normal web controllers.
- The browser (Vue) UI must work for **every** logged-in user; gating session
  auth would break it for non-allowlisted users with zero security gain.

### Further semantics

- Session identity restoration runs the full `yii\web\User::renewAuthStatus()`
  (session auth key check); the web UI's `authTimeout` / `absoluteAuthTimeout`
  are copied onto the API user component, so idle/absolute expiry matches the
  web UI and API activity keeps the session alive like normal page activity.
- The auto-login ("remember me") cookie alone does **not** authenticate API
  requests (`enableAutoLogin` stays off); core re-establishes the session on
  any page load before an island issues API calls.
- Token logins can never write into the browser session: the API user
  component stays session-less (`enableSession = false`); `SessionAuth` reads
  the session through a temporary window only.

### 4.4 Gate enforcement (2FA and other non-API gates)

Core's `GateFilter::getRequestClass()` infers `RequestClass::Api` purely from
`Yii::$app->user->enableSession === false`, which `BaseController` pins for every
REST request. A cookie-authenticated request would therefore be misclassified as
an API request and skip every gate that does not apply to API requests (2FA,
legal, onboarding, …) — so a user who passed only the first factor could reach
every endpoint. `SessionAuth` closes this: after restoring the identity it
re-classifies the request the way `GateFilter` would for a real browser session
(Ajax/FullPage, never Api) via `gateManager->findOpenGate()` and throws a 403
JSON when an open gate intercepts it. Gates that also apply to `Api` (e.g.
must-change-password, maintenance mode) are already enforced by the core
`GateFilter` on the same request and are not applied twice.

**Core-side follow-up:** the correct long-term fix is an explicit
"authenticated-by-session" signal on the request that `GateFilter` consumes,
instead of inferring the class from `enableSession`. The module-side guard above
is the interim; it should be revisited when this lands in core.

### 4.5 Impersonation (fail-closed on this branch)

`Impersonation::isActive()` short-circuits `false` while `enableSession` is off,
so core 1.19's private-content restriction (core #8372) would silently not apply
to a session-authenticated impersonation — an impersonating admin would see
through the API the private content the web UI hides. Both cases are handled:

- **Impersonate token** (`ImpersonateAuth`): the removed `isImpersonated` write
  is gone (commit "Fix impersonate token auth on HumHub 1.19"); the restriction
  is session-bound and does not apply to token requests. Re-applying an
  equivalent restriction to impersonate-token API access is a tracked follow-up.
- **Session impersonation** (`SessionAuth`): rejected outright (403) — detected
  from the `Impersonation::SESSION_KEY` session marker — until the core-side
  explicit-session signal (§4.4) lets the restriction evaluate correctly.

## 2. Endpoint gap analysis: core Vue islands vs. current REST module

What the islands consume today (core `enh/vuejs-integration`) vs. what
`/api/v1` offers.

### 2.1 Comment window / listing

| | Core island (`comment/comment/list` → `CommentJsonService::serializeWindow`) | REST (`GET comment/content/<id>`, `GET comment/parent/<id>`) |
|---|---|---|
| Pagination | Cursor/window: `commentId` + `direction=previous\|next` + `pageSize`, or anchored permalink window; returns `prevCount`, `nextCount`, `total` (incl. replies), `rootTotal` (root-only) | Offset: `page`/`limit`; returns `total`, `page`, `pages`, `links`, `results` |
| Reply previews | `children: {total, items, hasMore}` per root, one level deep | none (`childCount` number only) |
| Batch events | fires `EVENT_SERIALIZE_COMMENTS` once per window → `extensions` per comment (module extension point) | none |
| Guest gate | enforces `guestHideComments` (403) | API requires auth anyway |

### 2.2 Comment shape

Core (`CommentJsonService::serialize()`), per comment:
`id, contentId, parentCommentId, recordId, createdAt (ATOM), isEdited,
updatedAt, author (UserJsonService shape or null), blocked, message (raw
markdown), messageRenderOptions, attachmentsHtml, likes {count, liked},
canEdit, canDelete, canAdminDelete, permalink, children, extensions`.

REST (`CommentDefinitions::getComment()`):
`id, message (raw markdown, no render options), contentId, parentCommentId,
createdBy (id, guid, display_name, url), createdAt (DB format), likes {total},
files, childCount`.

Missing in REST: viewer-context permissions (`canEdit`/`canDelete`/
`canAdminDelete`), viewer like state (`liked`), blocked-author masking,
`messageRenderOptions` (client-side RichText envelope), `attachmentsHtml`,
`recordId`, `permalink`, `isEdited`/`updatedAt`, ATOM timestamps, `extensions`.

### 2.3 Comment mutations

| | Core island | REST |
|---|---|---|
| Create | `comment/create` — JSON `message`/`fileList`/`parentCommentId`, enforces one nesting level, 422 + `errors` map, returns full island comment shape | `POST comment?contentId=&parentCommentId=` — returns REST shape, 400 + `comment` errors key, no nesting-depth guard |
| Update | `comment/update` — GET returns raw markdown for editor, POST saves | `PUT comment/<id>` (no "fetch raw for editor" mode — REST shape already carries raw markdown) |
| Delete | `comment/delete` — supports admin delete with notification (`AdminDeleteCommentForm`: notify + reason), returns `{success}` | `DELETE comment/<id>` — plain delete, no notify/reason flow |
| Single | `comment/info` — `showBlocked=1` reveal, island shape | `GET comment/<id>` — REST shape, no blocked masking at all |

### 2.4 Likes

| | Core island (`like/*`) | REST |
|---|---|---|
| State | `info` → `{currentUserLiked, likeCounter}` (guest-allowed) | none (only `likes.total` embedded in content shapes) |
| Like / Unlike | `POST like/like`, `POST like/unlike` → same state shape | **no like/unlike endpoint at all** (`GET/DELETE like/<id>`, `GET like/find-by-object` only) |
| User list | `like/user-list` → `{total, users: [UserJsonService], hasMore, nextPage}`, limit clamped to `userListPaginationSize` | `GET like/find-by-object` → offset-paged `{id, createdBy(short), createdAt}` |

### 2.5 User shape

Core `UserJsonService::serialize()` (shared island shape, `<UserImage>` props):
`guid, displayName, url, imageUrl, contentContainerId, imageAlt, online`.

REST `UserDefinitions::getUserShort()`: `id, guid, display_name, url` —
snake_case naming, no `imageUrl`/`online`/`contentContainerId`/`imageAlt`.

## 3. Island endpoints (implemented on this branch)

Guiding principle: the core `*JsonService` classes are controller-agnostic —
**they are reused verbatim from new REST controllers instead of re-modelling
their output in `Definitions`**. Existing REST endpoints/definitions stay
untouched (they are a public, versioned contract also used by the legal data
export and third-party integrations; injecting viewer-dependent fields there
would change their semantics).

All routes below are **UNSTABLE / UI-COUPLED**: they serve the HumHub frontend
(the core Vue islands) 1:1 and may change together with core — they are not
part of the stable public REST contract. This is flagged in every action
docblock; there is no artificial `/internal/` path prefix (owner decision —
naming may still be revisited before merge).

### Final routes

| Method + route | Controller action | Core call / contract |
|---|---|---|
| `GET /api/v1/comment/window` | `rest/comment/window/index` | `CommentJsonService::serializeWindow()` — params `contentId`/`parentCommentId`, `commentId`, `direction`, `pageSize`; returns `{comments, prevCount, nextCount, total, rootTotal}` incl. per-root `children` previews and the `extensions` namespace (mirror of core `comment/comment/list`) |
| `GET /api/v1/comment/<id>/full` | `rest/comment/window/view` | `CommentJsonService::serializeComment()` — `?showBlocked=1` lifts the blocked-author mask (mirror of core `comment/comment/info`) |
| `POST /api/v1/comment/full` | `rest/comment/window/create` | Comment create from `message`/`fileList`/`parentCommentId` (+`contentId`), one-nesting-level guard, `canComment()` check; returns `serializeComment()` or `422 {"errors": {attr: [...]}}` (mirror of core `comment/comment/create`) |
| `PUT /api/v1/comment/<id>/full` | `rest/comment/window/update` | Comment save; returns `serializeComment()` or the 422 `errors` contract (mirror of core `comment/comment/update` POST mode) |
| `GET /api/v1/comment/<id>/full/edit` | `rest/comment/window/edit` | `{"message": <raw markdown>}` for the editor (mirror of core `comment/comment/update` GET mode) |
| `DELETE /api/v1/comment/<id>/full` | `rest/comment/window/delete` | Delete incl. optional `AdminDeleteCommentForm[notify]`/`[message]` author notification; returns `{"success": bool}` (mirror of core `comment/comment/delete`) |
| `GET /api/v1/like/info` | `rest/like/like/info` | `{currentUserLiked, likeCounter}` via `LikeService`, keyed by `recordId` (RecordMap id — exactly what `LikeButton.vue` sends); guest-allowed (mirror of core `like/like/info`) |
| `POST /api/v1/like` | `rest/like/like/like` | `LikeService::like()` after `canLike()`; returns the state shape (mirror of core `like/like/like`) |
| `DELETE /api/v1/like` | `rest/like/like/unlike` | `LikeService::unlike()`; returns the state shape (mirror of core `like/like/unlike`) |
| `GET /api/v1/like/user-list` | `rest/like/like/user-list` | `{total, users, hasMore, nextPage}` with `UserJsonService` rows and the `limit` clamp to `[1, userListPaginationSize]` (mirror of core `like/like/user-list`) |

Contract notes (all deliberate, for 1:1 island fidelity):

- Payloads are byte-compatible with the core JSON controllers — no REST
  envelope rewrap. Viewer-context fields (`canEdit`/`canDelete`/`likes.liked`),
  blocked-author masking, the `guestHideComments` gate, `extensions`, and
  `message`+`messageRenderOptions` all come from the delegated core services.
- Errors are HTTP exceptions (404/403 with Yii's JSON error body, same as the
  core controllers) and validation failures are `422 {"errors": ...}` — NOT
  the module's usual `400 {"code", "message"}` envelope. The pre-existing
  comment/like CRUD endpoints keep their envelope unchanged.
- The core `actionDelete` notification block (`CommentDeleted`) is currently
  duplicated in `WindowController::actionDelete()` because core has not
  extracted it into a service; when the core controllers are removed in favor
  of these endpoints, it should move into a core service.
- The admin-delete modal HTML (`comment/comment/get-admin-delete-modal`)
  remains a core route — it returns rendered widget HTML, not island JSON.

### Guest access

Core allows guests on some of these actions (comment window/single view, like
info) subject to `Content::canView()` and `guestHideComments`. The REST module
previously had no guest mechanism at all (401 for everything). Implemented
minimal mechanism: `BaseController::$guestAllowedActions` — a per-controller
list of action ids wired to the `CompositeAuth` authenticator's standard
`optional` list, honored **only while guest access is enabled globally**
(`AuthHelper::isGuestAccessEnabled()`), mirroring core's
`AccessControl::$guestAllowedActions` semantics. Requests with valid
credentials still authenticate normally; without credentials the action runs
as guest and is responsible for its own guest-safe authorization (all
delegated core services/`canView()` checks already are).

Declared lists: `WindowController` → `['index', 'view']`;
`LikeController` → `['info']` (its stable CRUD actions stay logged-in only).

Net effect: the islands can switch from the core `/comment/...`, `/like/...`
routes to `/api/v1/...` by swapping the base URL — auth stays the browser
session + CSRF header they already send today, guest behavior included.

## 4. Open questions for the owner

1. **Shape fidelity:** ~~serve the island payloads 1:1 under `/api/v1`?~~
   **Decided & implemented:** 1:1 fidelity, no REST envelope rewrap (see §3).
2. **Internal namespace:** ~~`/api/v1/internal/...` prefix?~~ **Decided:**
   plain routes flagged unstable/UI-coupled in docblocks and this document.
   Naming may be revisited before merge.
3. **HTML fragments:** `attachmentsHtml` (and the admin-delete modal, which
   stays a core HTML route) — acceptable in API responses, or should
   attachments become structured JSON + a client-side renderer first?
4. **Client wiring:** will the islands' fetch wrapper reuse `humhub.client`'s
   CSRF header mechanism as assumed? (The session-auth CSRF contract relies on
   `X-CSRF-Token`.)
5. **ImpersonateAuth breakage (pre-existing):** ~~fix on this branch or
   separately?~~ **Fixed on this branch** (own commit, intended to be
   cherry-picked to `develop` as an independent core-compat PR): the write to
   the removed `isImpersonated` property is gone. Applying core 1.19's
   impersonation private-content restriction to impersonate-token requests
   remains a separate follow-up (the API user component is session-less, so
   `Impersonation::isActive()` can never apply as-is).
6. **Rate limiting:** browser-session traffic will multiply API request volume
   — is throttling needed before the islands switch over?
