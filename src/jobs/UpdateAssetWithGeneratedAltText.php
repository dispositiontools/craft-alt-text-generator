<?php

namespace dispositiontools\craftalttextgenerator\jobs;

use craft\queue\BaseJob;
use dispositiontools\craftalttextgenerator\AltTextGenerator;

/**
 * Update Asset With Generated Alt Text queue job
 */
class UpdateAssetWithGeneratedAltText extends BaseJob
{
    public ?int $apiCallId = null;
    public ?string $type = "generated";


    
    public function execute($queue): void
    {
        // ...
        AltTextGenerator::getInstance()->altTextAiApi->updateAssetWithGeneratedAltText($this->apiCallId, $this->type);
    }

    protected function defaultDescription(): ?string
    {
        return "Update asset with generated alt text";
    }
}
