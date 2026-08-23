# Manual

## Available Endpoints 

Following RESTful API endpoints are available.

**Base URL:**

The base url for the endpoints of this module is: `https://yourhost/api/v1/`

Since HumHub 1.19 the platform itself ships API endpoints under
`https://yourhost/api/v2/`, in modernized conventions (ISO-8601 timestamps, camelCase,
plain HTTP status codes). This module's authentication methods apply to them as well — see
the **v2 APIs** below and `docs/api-stack.md`.

**Language**

Logged-in user's language will be used. Can be overwritten by `Accept-Language` header.


**v1 APIs (this module):**

- [User](https://marketplace.humhub.com/module/rest/docs/html/user.html)
- [Content](https://marketplace.humhub.com/module/rest/docs/html/content.html)
- [Post](https://marketplace.humhub.com/module/rest/docs/html/post.html)
- [Comment](https://marketplace.humhub.com/module/rest/docs/html/comment.html)
- [Like](https://marketplace.humhub.com/module/rest/docs/html/like.html)
- [Activity](https://marketplace.humhub.com/module/rest/docs/html/activity.html)
- [Authentication](https://marketplace.humhub.com/module/rest/docs/html/auth.html)
- [File](https://marketplace.humhub.com/module/rest/docs/html/file.html)
- [Notification](https://marketplace.humhub.com/module/rest/docs/html/notification.html)
- [Space](https://marketplace.humhub.com/module/rest/docs/html/space.html)
- [Content Topics](https://marketplace.humhub.com/module/rest/docs/html/topic.html)

**v2 APIs (endpoints shipped by HumHub core, 1.19+):**

- [Comment](https://marketplace.humhub.com/module/rest/docs/html/v2/comment.html)
- [Like](https://marketplace.humhub.com/module/rest/docs/html/v2/like.html)
- [Account](https://marketplace.humhub.com/module/rest/docs/html/v2/account.html)

**Module APIs** 

- [Calendar](https://marketplace.humhub.com/module/calendar/docs/swagger/calendar.html)
- [CFiles](https://marketplace.humhub.com/module/cfiles/docs/swagger/cfiles.html)
- [Tasks](https://marketplace.humhub.com/module/tasks/docs/swagger/task.html)
- [Wiki](https://marketplace.humhub.com/module/wiki/docs/swagger/wiki.html)
- [Mail](https://marketplace.humhub.com/module/mail/docs/swagger/mail.html)
- [Polls](https://marketplace.humhub.com/module/polls/docs/swagger/poll.html)
- [Survey](https://marketplace.humhub.com/module/survey/docs/swagger/survey.html)


The folder `/docs/html`contains HTML rendered documentations for all available API endpoints.

### Swagger / OpenAPI 3.0.0 

You can find Swagger documentation files in the `/docs/swagger` directory of this module.

### PostMan

`/docs/postman` contains a PostMan collection with all available requests.

