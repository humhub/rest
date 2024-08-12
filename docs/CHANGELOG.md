Changelog
=========

0.10.2 (Unreleased)
----------------------
- Enh #174: Implement new event to return all user data

0.10.1 (July 31, 2024)
----------------------
- Enh #173: Added possibility to change `created_at` of `content`
- Enh #116: Allow to set `show_at_dashboard` and `can_cancel_membership` on space membership creating

0.10.0 (July 16, 2024)
----------------------
- Enh #155: Add `requirements.php`
- Fix #164: Disable `mustChangePassword` check for impersonated access tokens
- Enh #170: Added new endpoint `GET /user/invite` for user pending invites
- Enh #171: Extended invite information in response of `GET /user/invite` endpoint

0.9.3 (December 8, 2023)
------------------------
- Fix #135: Add "last login" value to user endpoint
- Enh #47: Implementation of [the "Invite new users" endpoint](https://marketplace.humhub.com/module/rest/docs/html/user.html#tag/Invite)
- Enh #134: Implementation of the user account `visibility` property in [the "Update an existing user" endpoint](https://marketplace.humhub.com/module/rest/docs/html/user.html#tag/User/operation/updateUser)
- Enh #141: Tests for `next` version
- Fix #142: Fix visibility of the method `Controller::getAccessRules()`
- Fix #143: On user creation, status is always 1 even if set to another value

0.9.2 (June 14, 2023)
---------------------
- Enh #125: Add documentation for endpoint `user/get-by-authclient`
- Enh #128: Create User without Password
- Fix #130: Don't extend core User model

0.9.1 (May 26, 2023)
--------------------
- Fix #126: Fix user component initialization

0.9.0 (May 17, 2023)
--------------------
- Fix #110: Fix PHP Error in UserDefinition
- Enh #106: Allow to set `authclient` and `authclient_id` on user creating and updating
- Enh #112: Added support of HttpBearer and QueryParam auth methods
- Enh #117: Added support of user Impersonate
- Enh #113: Deleted unnecessary code used for Calendar And Task Modules
- Fix #114: Fix tests on soft delete of content
- Fix #122: Removed undefined properties

0.8.0 (March 10, 2023)
----------------------
- Fix: Update for HumHub version 1.14

0.7.1 (January 6, 2023)
-----------------------
- Fix #102: Update for version 1.13
- Fix #95: Add `updated_at` in metadata

0.6.1 (June 06, 2022)
--------------------
- Fix #91: Use Content-Type "application/json" for endpoint auth/login in request

0.6.0 (May 26, 2022)
-------------------
- Enh #41: Add Comment API endpoints

0.5.1 (May 16, 2022)
-------------------
- Fix #88: Increase min version for new field Content::locked_comments

0.5.0 (May 6, 2022)
-------------------
- Enh #85: Allow update of Content Topics
- Enh #85: Allow update of Content Metadata (Visibility, Archived, Pinned)
- Enh #70: Content: Add sorting option

0.4.3 (February 1, 2022)
------------------------
- Enh #73: Added "mustChangePassword" flag for users created using the API
- Enh: Don't provide auth token for disabled users on REST module side

0.4.2 (January 31, 2022)
------------------------
- Enh #72: Added Visibility as Content MetaData

0.4.1 (January 25, 2022)
------------------------
- Enh #74: Response space container ID
- Enh #75: Attach files to content

0.4.0  (January 19, 2022)
-------------------------
- Enh #33: Move endpoints to external modules
- Fix #60: Cleanup code for prepare params
- Enh #86: Docs for polls module
- Fix #67: Update docs for container(space/user) tags
- Enh #74: Response space container ID
- Enh #75: Attach files to content

0.3.1  (Unreleased)
--------------------------
- Enh: Use controller config for not intercepted actions
- Fix #57: Use getBodyParam() to extract the 'role' body parameter for the space membership

0.3.0  (February 22, 2021)
--------------------------
- Fix #51: Remove group.space_id for compatible with Humhub 1.8

0.2.1  (February 17, 2021)
--------------------------
- Enh #45: Files Endpoint - Don’t require target folder id to create root automatically

0.2.0-beta.1  (November 23, 2020)
---------------------------------
- Enh: New Endpoints for Mail module
- Enh: Added permission checks for regular user access
- Fix #42: Activity Module Endpoint
- Fix #43: Swagger doc is incorrect for user "display_name" for User endpoints
- Fix: Renamed "/like/findByRecord" endpoint to "/like/find-by-object"

0.1.4  (October 13, 2020)
-------------------------
- Enh: User new endpoints, find-by-username and find-by-email
- Enh: Added current User details endpoint

0.1.3  (August 5, 2020)
-------------------------
- Fix: HumHub 1.6 compatibility issues

0.1.2  (January 31, 2020)
-------------------------
- Enh: Added User group endpoint

0.1.1  (January 17, 2020)
-------------------------
- Initial release in marketplace
- Chg: Removed 'members' attribute from 'Space' output object
- Enh: Added Space Membership Endpoint

0.1.0  (20 December, 2019)
---------------------------
- Initial release in marketplace
- Enh: Various new endpoints and features

0.0.1  (Unrelased)
------------------------
Initial release
