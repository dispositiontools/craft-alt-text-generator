<?php

namespace dispositiontools\craftalttextgenerator\jobs;

use craft\queue\BaseJob;
use dispositiontools\craftalttextgenerator\AltTextGenerator;

/**
 * Request Human Alt Text Review queue job
 */
class RequestHumanAltTextReview extends BaseJob
{
    public ?int $assetId = null;
    public ?int $apiCallId = null;
    public ?int $humanRequestUserId = null;
    
    public function execute($queue): void
    {
        AltTextGenerator::getInstance()->altTextAiApi->callAltTextAiHumanReviewAipi($this->apiCallId, $this->humanRequestUserId);
    }

    protected function defaultDescription(): ?string
    {
        return "Request human review of image alt text";
    }
}
