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
    public ?bool $asyncApi = false;
    public ?bool $showHumanReview = true;
    public ?bool $webhookSetInAccount = true;
    public ?bool $apiKeyActive = false;
    public ?string $modelName = "describe-regular";
    public ?string $lang = "en";
}
