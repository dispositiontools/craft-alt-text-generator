<?php

namespace dispositiontools\craftalttextgenerator\models;

use craft\base\Model;
use craft\helpers\App;

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
    public ?string $customField = 'alt';
    public ?string $useImagePreviewUrl = "never"; // no , always, forLargeImages



    /**
     * Returns the site group’s name.
     *
     * @param bool $parse Whether to parse the name for an environment variable
     * @return string
     * @since 3.7.0
     */
    public function getApiKey(bool $parse = true): string
    {
        return ($parse ? App::parseEnv($this->apiKey) : $this->apiKey) ?? null;
    }

    /**
     * Sets the site’s name.
     *
     * @param string $name
     * @since 3.7.0
     */
    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

}
