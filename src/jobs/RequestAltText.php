<?php

namespace dispositiontools\craftalttextgenerator\jobs;

use craft\queue\BaseJob;
use dispositiontools\craftalttextgenerator\AltTextGenerator;

/**
 * Request Alt Text queue job
 */
class RequestAltText extends BaseJob
{
    public ?int $assetId = null;
    public ?int $requestUserId = null;
    public ?bool $overwrite = false;
    public ?string $actionType = "Action";
    
    public function execute($queue): void
    {
        AltTextGenerator::getInstance()->altTextAiApi->callAltTextAiAipi($this->assetId,  $this->actionType, false, $this->requestUserId, $this->overwrite);
    }

    protected function defaultDescription(): ?string
    {
        return "Request alt text";
    }
}
