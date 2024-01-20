# Release Notes for Alt text Generator


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
 
