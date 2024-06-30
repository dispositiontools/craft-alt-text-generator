<?php

namespace dispositiontools\craftalttextgenerator\models;

use craft\base\Model;
use DateTime;

/**
 * Alt Text Ai Api Call model
 */
class QueueAllReport extends Model
{
    public ?int $numberOfCredits = 0;
    public ?int $numberOfAssetsWithNoAltText = 0;
    public ?int $numberOfAssetsQueuedWithNoAltText = 0;
    public ?int $numberOfAssetsRejectedWithNoAltText = 0;
    public ?int $numberOfAssetsWithAltText = 0;
    public ?int $numberOfAssetsQueuedWithAltText = 0;
    public ?int $numberOfAssetsRejectedWithAltText = 0;
    public ?array $assets = [];

    
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }
}
