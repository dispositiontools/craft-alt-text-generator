<?php

namespace dispositiontools\craftalttextgenerator\jobs;

use Craft;
use craft\queue\BaseJob;
use dispositiontools\craftalttextgenerator\AltTextGenerator;
use dispositiontools\craftalttextgenerator\errors\RequestAltTextException;


/**
 * Request Alt Text queue job
 */
class RequestAltText extends BaseJob {
	public ?int $assetId = null;
	public ?int $requestUserId = null;
	public ?bool $overwrite = false;
	public ?string $actionType = "Action";
	public ?int $siteId = null;

	public function execute($queue): void {
		if (empty($this->siteId)) {
			$this->siteId = Craft::$app->getSites()->getCurrentSite()->id;
		}

		$jobResult =   AltTextGenerator::getInstance()->altTextAiApi->callAltTextAiAipi($this->assetId,  $this->actionType, false, $this->requestUserId, $this->overwrite, $this->siteId);


		if (isset($jobResult['error']) && $jobResult['error']) {
			$errorMessage = "Error with Request Alt Text Job with meaage: " . $jobResult['errorMessage'];
			throw new RequestAltTextException($errorMessage);
		}
	}

	protected function defaultDescription(): ?string {
		return "Request alt text";
	}
}
