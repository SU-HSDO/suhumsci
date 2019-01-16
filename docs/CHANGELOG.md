# HumSci

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
