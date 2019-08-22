# HumSci

8.1.6
--------------------------------------------------------------------------------
_Release Date: 2019-08-22_

* add known hosts (1821b1d3)
* added github keyscan (2c5c080b)
* added user display name field (618a6f80)
* updated dependencies (e3c6e610)
* HSD8-675 Dont remove custom permissions like "administer nodes" (#374) (dd818f07)
* disable fast404 (8fef836f)
* improve circleci workflow (5044629b)

8.1.5
--------------------------------------------------------------------------------
_Release Date: 2019-08-08_

* Fix lazy loading of masonry images by using a different lazy loading library (5d1fe138)
* Updated all dependencies (67941d70)
* dont throw error if getstatuscode not defined (f352bd0b)
* HSD8-684 Added Views Ical module (d0488d3c)
* HSD8-686 stage file proxy (#355) (55f6d36a)
* HSD8-678 Map course instructor role (#345) (af3834cf)
* lazy load images (#336) (069085c4)
* added margin to hero with text overlay when in components field (4d28943e)
* HSD8-681 prune courses (#349) (74e6a8bf)
* HSD8-682 Fix table pattern when used in views (#346) (c725464f)
* updated blt to 10.x-dev (#337) (fdc04c04)

8.1.4
--------------------------------------------------------------------------------
_Release Date: 2019-07-24_

* Fixed circleci deploy branch ignore filter (0bc0ec8c)
* Fixed add field form (146deefc)
* updated composer dependencies (13ee9a49)
* changed hero small image style size (3ab62bb8)
* Fix group block links from generating js errors (#348) (13850549)
* dont hide horizontal card category for math theme (37ed5dea)
* patched extlink (895b9130)
* fixed hero update hook to prevent field being deleted during config sync (55fd40b6)
* Hsd8 667 hires image effect (#334) (574a1f38)
* MR-23 Hero Text Overlay Addition (#316) (3dc389f1)
* HSD8-676 Wrap views results when the view is grouped (#333) (5404c395)
* Set course api endpoint to max age of 2 hours (215158fc)
* MR-26 Move Hero with Text Overlay into SCSS (#315) (27367929)
* HSD8-677 Full width lead font (#331) (510fdffa)
* Deleted package.json and package-lock.json in nested repos (#335) (9f5ddb0f)

8.1.3
--------------------------------------------------------------------------------
_Release Date: 2019-07-10_

* install dependencies for circleci when doing github release (535c749)
* fixed blt github release command (22852a3)
* Fix behat tests when cron takes too long because of course importers (5c5c740)
* patched paragraphs for fix for latest field_groups (2386893)
* MR-25 Row with Background CSS (#317) (da87383)
* Url encode course link (#313) (5007558)
* updated field_group and the configs that it is used in (2853362)
* improved letsencrypt commands (82d9dfe)
* reorderd full html filters (8191a5f)
* HSD8-666 Provision gavin-wright.humsci.stanford.edu (#325) (eaad7e6)
*  Moved CircleCI Robo to a BLT command (#318) (0debf0d)
* Fix table header color (#324) (002607c)
* updated launch document and filter order (3b9fecb)
* modified github release contents from circleci (bc96e01)
* dont deploy violinist branches (2e59ac1)

8.1.2
--------------------------------------------------------------------------------
_Release Date: 2019-06-26_

* fixed github release command (ea7d8f1)
* prevent infinite releases in circleci (c67b6fd)
* updated configs after update (cd3f5c5)
* Test circleci workflow (#301);* Use circleci workflow to deploy to acquia;* fixed deploy branch command;* fixed array_rand;* use shell commands instead of robo;* add known hosts;* add known hosts;* changed ssh-keyscan;* remove dryrun;* run only deploy task;* set git config email and name;* deploy tag;* use variables in deploy for tag;* removed unused code;* deploy any tag;* updated documentation;* use release it to automatically create a release;* Use robo commands instead of npm package to do github release;* cc fix;* parse git url instead of hard coded git uri (5c5cdc0)
* Hsd8-653 Generic 3 column 1-5-1 variant (#307);* HSD8-653 Added 1-5-1 variant to generic three column pattern;* changed wildcard class to specific classes;* fixed update hook to ignore user roles (ee09c10)
* ignore custom user roles (e9e65e9)
* Adjusted configs and saml to allow for workgroup mapping (#306) (722b36f)
* Removed williams since heidi_williams__humsci is in place (#299) (cd5e57b)
* HSD8-659 Adding bottom margin for group blocks to space them better (#287);* Changed rules contrib module to dev branch;* Updated dependencies and removed core patch no longer needed;* changed phpunit config;* HSD8-659 Adding bottom margin for group blocks to space them better;* HSD8-649 added content_access module;* removed content_lock and core patch;* removed orphaned config;* update hook to remove configs;* removed orphaned config;* provision dfetter;* changed block_group to group_block;* fixed error when trying to delete block in group and when trying to add block;* removed unneccessary class;* removed unrelated link in document (942359f)
* updated dependencies (10fe03c)
* added more to new site documentation (2d1cfed)

8.1.1
--------------------------------------------------------------------------------  
_Release Date: 2019-06-13_

* HSD8-649 added content_access module
* Provision dfetter
* Provision heidi-williams.humsci and move williams to it.
* Removed content_lock

8.1.0
--------------------------------------------------------------------------------  
_Release Date: 2019-05-22_

* Updated Drupal core to 8.7.1
* Stubbed out Math sub-theme
* Allow basic page paragraph fields to reorganize paragraph orders
* ignore custom migrate entities
* HSDO-1246 Provision Williams site
* HSD8-641 refactor Capx urls to chunk up into 25 profile paged urls
* MR-9 Events and seminars pattern for math theme


8.0.22
--------------------------------------------------------------------------------  
_Release Date: 2019-04-24_

* HSD8-616 Ignore status key on migration configs, set initial status to disabled and enable capx migration when credentials are added
* deleted hs_capx_images migration since we can now do this in the hs_capx migration directly
* improved cache tags mechanism for views

8.0.20
--------------------------------------------------------------------------------  
_Release Date: 2019-03-20_

* Fixed CAPx workgroup tagging: made the current url configuration more granular.
* Updated dependencies for security vulnerabilities.

8.0.19
--------------------------------------------------------------------------------  
_Release Date: 2019-03-06_

* HSD8-596 Added plugin for clone action to change field values
* HSD8-600 Generic 3 column pattern with variants
* HSDO-1240 duboislab site provision
* HSD8-318 Preview videos in media library

8.0.18
--------------------------------------------------------------------------------  
_Release Date: 2019-02-20_

* HSD8-578 Added margin to horizontal cards used in ECK fields
* HSD8-536 Accordion Pattern & Paragraph
* HSD8-355 Changed config ignore to ignore only parts of google analytics
* HSD8-579 Use ultimate cron to compartmentalize each migration importer 
* HSD8-572 Style the login portal page to be closer to material theme
* HSD8-312 HSD8-504 External LInk icon placement and styles
* HS-113 CAPx importer fixes
* HSD8-576 View Field title (with optional override)
* HSD8-515 Use color module to change colors on the site
* HSD8-297 Fixed WYSIWYG toolbar when the content is really long
* HSD8-555 Filter shortcuts the user doesn't have permission to access
* Enable honeypot on all webforms
* Research Areas can now be placed in a menu
* Updated dependencies

8.0.17
--------------------------------------------------------------------------------  
_Release Date: 2019-02-06_

* HSD8-531 refactor admin theme to remove material admin theme
* HS-110 Added course code integer field and mapping
* HSD8-545 events exporter for MRC to create an XML feed to be consumed on mathematics site
* HSD8-546 node clone action allowing users to clone a node 1 to 10 times. 

8.0.16
--------------------------------------------------------------------------------  
_Release Date: 2019-01-24_

* HSD8-351 Allow courses importer urls to be catalog url
* HSD8-249 added help text for no link menu items
* HSD8-309 Ignore revisions for used in count on media list
* HSD8-530 CAPx workgroup tagging for taxonomy fields
* HSD8-561 CAPx external link mapping to content
* HSD8-569 Audio media field in events post event area
* HSD8-560 Fixed error reports about entity type not installed
* Fixed max file size limits
* HSD8-551 anchor and email links and better link widget for wysiwyg
* HSD8-540 Added granular permission and link to add sunetid user
* Added stanford_ssp module

8.0.15
--------------------------------------------------------------------------------  
_Release Date: 2019-01-09_

* HSD8-268 Migrate improter ui for site managers to execute.
* HSD8-477 Set initial ECK permissions on ECK type creation to allow public viewing
* HSD8-497 Delete old node revisions on cron run
* HSD8-513 added color background option to text area paragraph
* HSD8-532 Allow unlimited event videos
* HSD8-501 fix image captions for mobile
* HSD8-359 Configure shild module for dev and test
* HSD8-529 audio embeding with stanford_media
* HSD8-385 customize shortcuts with shortcut menu module
* HSD8-463 Decode local urls before saving
* Allow removal of course importer url

8.0.14
--------------------------------------------------------------------------------  
_Release Date: 2018-11-29_

* HSD8-486 always show alt text column in admin media listing
* HSD8-480 Collapse main menu items in mobile when a new item is opened in the same level.
* HSD8-489 Fix encoding of ampersand in linked card titles.
* HSD8-503 Set default taxonomy path auto pattern
* HSD8-500 Created a UI to change the url for the event importer
* HSD8-479 Limit the conditions a users sees when configuring a block or menu position rule
* Media updated to check for existing items when a new item is being uploaded.

8.0.13
--------------------------------------------------------------------------------  
_Release Date: 2018-11-14_

* Alter views queries to consolidate date field sorting allowing multiple content types to be sorted togeather.
* Provided a new block available in layout builder that allows a user to nest fields within the block.
* Prevent sensitive permissions from being enabled on roles.
* New view to easily manage content of each content type on its own page.
* Added the ability to add classes to videos embeded in wysiwyg fields
* Added the ability to add image captions/credits to image fields
* Allow for taxonomy term pages to be customized more accurately

8.0.12
--------------------------------------------------------------------------------  
_Release Date: 2018-10-29_

* Capx people importer with a UI to set credentials and choose a workgroup or organization
* Allow users to hide a block title without the need for administer blocks permission
* Replace date time picker to use the browser default
* Changed events importer link text
* Create Howard and Lindsey Users on new sites
* Allow a field lable to be displayed as an H2 or regular text
* Set default form displays for future sites

8.0.11
--------------------------------------------------------------------------------  
_Release Date: 2018-10-11_

- New sites: symsys and mathemtics
- Force config entity prefixing
- allow for custom subthemes
- New subthemes for archaeology and francestanford
- Removed and added a couple fields for productizing
- Several style adjustments.
- Changed core "People" to "Users"
- Redesigned bugherd module to allow all webhooks to point to the same domain but differnt urls

8.0.10
--------------------------------------------------------------------------------  
_Release Date: 2018-09-26_

- Removed masquerade block and added a menu item under people.
- Added custom permission for user list page
- Added operation to edit user roles quicker for site managers
- Improved test coverage
- Use the block description instead of the admin label on the block display
- Added visual indicator for links to unpublished nodes
- Ignore status of baseline views.
- Fixed double page titles on 404 pages
- Custom login block with prefix contextual text input
- Created config split to allow nobots to be disabled on live sites.
- Set new menu items to be expanded by default.

8.0.9
--------------------------------------------------------------------------------  
_Release Date: 2018-09-14_

- Style changes for MRC code
- Allow MRC year picker back 30 years.
- Expose views reset buttons and use ajax to reset
- Patched Masquerade module.
- Large amount of Code Climate cleanup

8.0.8
--------------------------------------------------------------------------------  
_Release Date: 2018-08-30_

- Integrated with CircleCI for automated testing.
- Set up initial behat test to test all links in the main menu.
- Began PHPUnit Tests to increase code coverage.
- Added Table text filter to convert tables into structured divs.
- Added the ability to add paragraphs between others without the need to drag and drop from the bottom.
- Improved main menu when screen is the same size as the page.
- Fixed the ability to use menu link weight module for other users.
- Added a form for the user to change the URL of course importers.
- Responsive div table styles.
- Allow up to 5 lines of text in the site lockup. 
- Link the event speaker to a person node if one exists.

8.0.7
--------------------------------------------------------------------------------  
_Release Date: 2018-08-16_

- Node edit protection with javascript to prevent un-intentional progress loss.
- Added better responsive image styles
- Bugfix on year date widget
- Added changes for accessibility issues
- Increased cache time
- Added site style options in the theme settings.

8.0.6
--------------------------------------------------------------------------------  
_Release Date: 2018-08-08_

- Full HTML format
- Font awesome CKEditor Button
- People node display defaults
- provisioned mrc, francestanford, and swshumsci-sandbox

8.0.5
--------------------------------------------------------------------------------  
_Release Date: 2018-08-01_

- Use search_api for site search, allowing us to exclude some course nodes from being indexed and take up all the search 
  result space
- Updated news content type
- Several front end style improvements.

8.0.4
--------------------------------------------------------------------------------  
_Release Date: 2018-07-19_

- Fixed Front end styling in the theme
- Added course content type
- Added course importer
- Added local path for course importer to target, allowing us to defined custom guids needed by the importer

8.0.3
--------------------------------------------------------------------------------  
_Release Date: 2018-07-12_

- Several front end styling tweaks

8.0.2
--------------------------------------------------------------------------------  
_Release Date: 2018-07-05_

- Person content type
- Research Area content type
- View changes
- SAML workgroup mapping
- Force HTTPS

8.0.1
--------------------------------------------------------------------------------  
_Release Date: 2018-06-21_

- Initial Release
