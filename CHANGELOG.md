# Release Notes for Alt text Generator

## v5.1.1 - 2024-07-01

### Fixed
- An issue where the queue all function was incorrectly counting the number of added assets fixing (Issue #6)[https://github.com/dispositiontools/craft-alt-text-generator/issues/6] with thanks to (@sgtpenguin)[https://github.com/sgtpenguin]

## v5.1.0 - 2024-06-30

> [!NOTE]  
> Alttext.ai have removed the ability to request a human review. This option has now been removed from the plugin.

### Added 
- A queue all report which shows which elements were added via the utility.
- The option to resubmit a image to Alttext.ai through the queue all utility.
- Queue job logging to see if there are any issues when requesting alt text from alttext.ai.

### Removed
- Alttext.ai have removed the ability to request a human review. This option has now been removed from the plugin.


## v5.0.1 - 2024-05-06

### Fixed
- Update to the element action javascript that was causing a problem with when selecting elements

## v5.0.0 - 2024-05-04

### Fixed
- An issue that stopped people trying out the API with a trial alttext.ai account
- Fixed an issue where errors where not being correctly stored and not shown
- Changed the default setting for using async to false so that most people get the results immediately

### Added
- Craft CMS 5 compatability
- The ability to resubmit an image to alttext.ai 
- A new setting to give the ability to choose from the different (language models)[https://alttext.ai/docs/webui/account/#style-and-level-of-detail]
- A new setting to allow you to choose the language the alttext.ai returns
- The ability to edit the alt text before syncing to an asset


## v1.0.5 - 2024-01-20

### Fixed
- Fixed an issue where webhook responses were being saved as a json dump in webroot. 

## v1.0.4 - 2023-11-09

### Fixed
- Fixed an error that occurred when calling the alttext.ai account details when there wasn't an active subscription (for instance when in a free trial, or using it in Pay as you go mode). Fixing (Issue #2)[https://github.com/dispositiontools/craft-alt-text-generator/issues/2]

## v1.0.3 - 2023-10-26

### Added
- Added some error checking for when API keys are incorrect or are no longer active
- Updated some caching of API credits

## v1.0.2 - 2023-10-18

### Fixed
- Queuing all images from the utility

## v1.0.1 - 2023-10-15

### Added
- Some error checking when the API key is missing

## v1.0.0 - 2023-10-14
Initial release of Disposition Tools Alt Text Generator
- Generate image alt text with alttext.ai
- Queue all images without alt text
- Use it fully automatically or with a review process
- Automatically generate for new images
- Queue images from asset element page with element actions
 
