<?php

namespace dispositiontools\craftalttextgenerator\models;

use craft\base\Model;

/**
 * Alt text Generator settings
 */
class Settings extends Model
{
    public ?string $apiKey = null;
    public ?bool $generateOnSave = false;
    public ?bool $useAltTextImmediately = false;
    public ?string $securityCode = null;
    public ?bool $asyncApi = true;
    public ?bool $showHumanReview = true;
    public ?bool $webhookSetInAccount = true;
    public ?bool $apiKeyActive = false;
}
