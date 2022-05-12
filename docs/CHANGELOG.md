Changelog
=========

0.5.0 (May 6, 2022)
-------------------
- Enh #85: Allow update of Content Topics
- Enh #85: Allow update of Content Metadata (Visibility, Archived, Pinned)
- Enh #70: Content: Add sorting option
- Fix #88: Increase min version for new field Content::locked_comments

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
