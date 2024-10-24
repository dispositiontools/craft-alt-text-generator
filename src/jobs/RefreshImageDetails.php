<?php

namespace dispositiontools\craftalttextgenerator\jobs;

use craft\queue\BaseJob;
use dispositiontools\craftalttextgenerator\AltTextGenerator;

/**
 * Refresh Image Details queue job
 */
class RefreshImageDetails extends BaseJob
{
    public ?int $apiCallId = null;
    public ?int $humanRequestUserId = null;
    
    
    public function execute($queue): void
    {
        // ...
        AltTextGenerator::getInstance()->altTextAiApi->refreshImageDetailsFromAltTextAi($this->apiCallId, $this->humanRequestUserId);
    }

    protected function defaultDescription(): ?string
    {
        return "Request refresh of image details from alttext.ai";
    }
}
