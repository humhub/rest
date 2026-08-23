# This module and the platform API stack

HumHub 1.19 ships an HTTP API framework in core (`humhub\components\api\`): base
controller, request/response conventions, URL-rule registration, the serialize
extension point and browser-session authentication. Core's own endpoints live next
to the modules that own them (`humhub\modules\<module>\controllers\api\`) and answer
under `/api/v2`. The full picture is in the core docs,
`docs/develop/concept-api.md`.

This module builds on that framework and adds what a platform API needs beyond it.

## What this module provides

- **Machine authentication** — JWT, HTTP Bearer, query-param bearer, HTTP Basic and
  impersonate tokens, plus the user allowlist and the admin configuration UI.
  Collected in `components/auth/AuthMethods.php`.
- **`/api/v1`** — this module's own endpoint surface and wire shapes, unchanged
  (`docs/swagger/*.yaml` → `docs/html/`).
- **`components/BaseController`** — the base class other modules extend for their
  own `/api/v1` endpoints (see `DEVELOPER.md`).

## Authentication model

Two questions worth keeping apart:

**Which methods exist?** Core ships browser-session authentication only. This module
adds the token methods and contributes them to *every* API controller of the platform
through core's collect event:

```php
// config.php
[BaseController::class, BaseController::EVENT_COLLECT_AUTH_METHODS, [Events::class, 'onCollectApiAuthMethods']],
```

So on an installation with this module, the core endpoints can be called with a token
too; on a core-only installation they can only be reached from a logged-in browser
session.

**Which endpoints accept which method?** Contributed token methods apply everywhere.
Browser-session authentication is an opt-in *per controller*
(`humhub\components\api\BaseController::$enableSessionAuth`, default off) and core
enables it only for the endpoints its own UI needs. `/api/v1` is therefore
token-only: a session cookie authenticates nothing here.

That asymmetry is deliberate. Session authentication bypasses the user allowlist —
it has to, since the browser UI must work for every logged-in user — and endpoints
written for token clients may have their authorization written for that narrower
threat model. They must not silently become reachable from any logged-in browser
session.

Ordering is part of the contract: contributed token methods run BEFORE session
authentication, so a request carrying a valid token authenticates as the token user
even when a session cookie is present ("token wins"). The fall-through is not
uniform: `JwtAuth` returns `null` on failure (the next method gets a turn), while the
Bearer/QueryParam/Basic/Impersonate methods throw on an invalid credential, so a
malformed token yields 401 instead of downgrading. On core endpoints that allow guests
(`humhub\components\api\BaseController::$guestAllowedActions`, honored only while guest
access is enabled platform-wide) a missing or invalid credential downgrades to guest;
`/api/v1` has no guest-readable actions.

## Guarding the URL space

API controllers are reachable through Yii's fallback routing (`/rest/<controller>/
<action>`, `/<module>/api/<controller>/<action>`), which would bypass the API URL
rules and their verb constraints. Both layers guard against it:

- this module prepends a `rest/<tmpParam:.*>` catch-all for every request and
  `BaseController::beforeAction()` hard-fails any request whose path is not under
  `api/v1/`,
- core does the same for its own controllers (`ApiRules::offPrefixGuard()` plus the
  `api/v2/` check in its base controller).

Both require pretty URLs, as the API always has.

## Documentation layout

`docs/swagger/` holds the OpenAPI sources, one document per module, rendered to
`docs/html/` by `build-all.sh`:

- the flat files are this module's `/api/v1` surface,
- `v2/` documents the endpoints **core** ships (`docs/html/v2/`), with `v2/common.yaml`
  holding the shared schemas, parameters, error responses and security schemes.

The v2 documents live here only until core ships the Swagger sources for its own
endpoints — keeping them in their own directory is what makes that a move rather than a
rename, and it leaves every published `/api/v1` documentation URL untouched.

## Version bounds

This module version requires core 1.19 (`humhub.minVersion`) — it uses the core
framework's collect event. The previous module line still needs a `humhub.maxVersion`, so
the marketplace does not offer a version without the core stack for 1.19+. Those fields
are marketplace metadata, not runtime enforcement: core does not evaluate them when
loading a module, so an administrator copying an outdated module in by hand bypasses
them.

## Open points

1. **Rate limiting** — browser-session traffic (the core Vue islands) multiplies API
   request volume. Throttling should be considered before a stable release.
2. **Impersonate-token restriction** — applying core 1.19's impersonation
   private-content restriction to impersonate-token API access is a tracked
   follow-up.
3. **v1 on the core stack** — `/api/v1` still carries its own base controller, error
   envelope and serializers. Reimplementing it over the core framework and
   serializers is the next consolidation step; its wire shapes stay as documented in
   `docs/swagger/`.
4. **v2 conventions** — ISO-8601 timestamps and camelCase field names are what core's
   `/api/v2` uses; the corresponding modernization of the v1 shapes is collected in
   the issue "v2: ISO-8601 timestamps and shape modernization".
