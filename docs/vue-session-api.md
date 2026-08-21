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
user even when a session cookie is present. An *invalid* token falls through to
session auth (standard CompositeAuth fall-through). Guests without token and
session get the usual 401 JSON.

### CSRF contract

- Session-authenticated **state-changing** requests (POST/PUT/PATCH/DELETE)
  require a valid Yii CSRF token: `X-CSRF-Token` header (what `humhub.client`
  sends, fed from the `csrf-token` meta tag) or `_csrf` body param.
- Missing/invalid token → **403 JSON** (`ForbiddenHttpException`), never an
  HTML error page (response format is forced to JSON for `/api/` early in
  `Events::onBeforeRequest`).
- GET/HEAD/OPTIONS are exempt. Token-authenticated requests remain CSRF-exempt
  exactly as before.
- Implementation detail: `BaseController` keeps `enableCsrfCookie = false` for
  API responses; `SessionAuth` re-enables cookie lookup only while validating,
  because the browser's true token lives in the `_csrf` cookie (core default).

### Setting

- `enableSessionAuth` (module setting, checkbox on the admin config form,
  `ConfigureForm`). **Default: enabled** on this branch — owner decision for
  the Vue experiment; the upstream default may change on merge.

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

## 3. Convergence proposal (per endpoint)

Guiding principle: the core `*JsonService` classes are controller-agnostic —
**reuse them from new REST controllers instead of re-modelling their output in
`Definitions`**. Existing REST endpoints/definitions stay untouched (they are a
public, versioned contract also used by the legal data export and third-party
integrations; injecting viewer-dependent fields there would change their
semantics). The owner explicitly allows new, "internal" REST endpoints.

| Island need | Proposal |
|---|---|
| Comment window | New `GET /comment/window` (`rest/comment/window/list`) with `contentId`/`parentCommentId`, `commentId`, `direction`, `pageSize` → return `CommentJsonService::serializeWindow()` verbatim |
| Single comment (island shape) | New `GET /comment/<id>/full` (`?showBlocked=1`) → `serializeComment()` |
| Create/update for islands | New `POST /comment/full?contentId=…` and `PUT /comment/<id>/full` returning `serializeComment()` with core's 422 `errors` contract (or: extend existing actions with a `?format=full` switch — less clean, mixes error contracts) |
| Admin delete w/ notification | New `DELETE /comment/<id>/full` accepting `notify`/`message` (mirrors `AdminDeleteCommentForm`) |
| Like state / like / unlike | New `GET /like/info`, `POST /like`, `DELETE /like` keyed by `model`+`pk` (RecordMap), returning `{currentUserLiked, likeCounter}` |
| Like user list | New `GET /like/user-list` returning `{total, users, hasMore, nextPage}` via `UserJsonService` |
| User shape | Use `UserJsonService` in all new endpoints; leave `UserDefinitions` untouched |
| `extensions` batch event | Comes for free by reusing `CommentJsonService` (fires `EVENT_SERIALIZE_COMMENTS`) |

Net effect: the islands can switch from `/comment/...` core routes to
`/api/v1/...` by swapping the base URL + auth stays the browser session +
CSRF header they already send today.

Naming/versioning: mark these routes as **internal** (serving the HumHub
frontend, shape may change with core) — either under a `/api/v1/internal/…`
prefix or via documentation flag in swagger — so they don't freeze into the
public API contract.

## 4. Open questions for the owner

1. **Shape fidelity:** serve the island payloads 1:1 under `/api/v1`
   (recommended above) or migrate the Vue clients to REST-envelope conventions
   (offset pagination, `code`/`message` errors)? 1:1 keeps the islands
   backend-agnostic and diff-free; REST-envelope would make them "real" public
   API consumers but requires island rework (window pagination is UX-relevant).
2. **Internal namespace:** `/api/v1/internal/...` prefix, or plain routes with
   an "internal/unstable" documentation flag?
3. **HTML fragments:** `attachmentsHtml` (and the admin-delete modal, which
   stays a core HTML route) — acceptable in API responses, or should
   attachments become structured JSON + a client-side renderer first?
4. **Client wiring:** will the islands' fetch wrapper reuse `humhub.client`'s
   CSRF header mechanism as assumed? (The session-auth CSRF contract relies on
   `X-CSRF-Token`.)
5. **ImpersonateAuth breakage (pre-existing):** `ImpersonateAuth` sets
   `Yii::$app->user->isImpersonated`, a property removed by core 1.19's
   impersonation refactor (core PR #8372) — `AuthCest::testImpersonateByAdmin`
   fails against current core on a clean checkout. Fix on this branch or
   separately?
6. **Rate limiting:** browser-session traffic will multiply API request volume
   — is throttling needed before the islands switch over?
