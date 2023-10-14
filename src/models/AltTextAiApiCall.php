<?php

namespace dispositiontools\craftalttextgenerator\models;

use craft\base\Model;
use DateTime;

/**
 * Alt Text Ai Api Call model
 */
class AltTextAiApiCall extends Model
{
    public ?int $id = null;
    public ?int $requestUserId = null;
    public ?int $assetId = null;
    public ?int $siteId = null;
    public ?string $requestType = null;
    public ?DateTime $dateRequest = null;
    public ?string $request = null;
    public ?string $requestId = null;
    public ?DateTime $dateResponse = null;
    public ?string $response = null;
    public ?string $originalAltText = null;
    public ?string $generatedAltText = null;
    public ?string $altTextSyncStatus = null;
    public ?DateTime $dateDeleted = null;
    public ?DateTime $dateCreated = null;
    public ?DateTime $dateUpdated = null;
    public ?string $uid = null;
    
    
    public ?string $humanGeneratedAltText = null;
    public ?string $humanAltTextSyncStatus = null;
    
    public ?DateTime $humanDateRequest = null;
    public ?string $humanRequest = null;
    
    public ?DateTime $humanDateResponse = null;
    public ?string $humanResponse = null;
    
    public ?int $humanRequestUserId = null;
    
    
    // User / On Save / Mass queued
    
    /*

    $fieldsToUpdate = [
        'requestUserId',
        'assetId',
        'siteId',
        'requestType',
        'dateRequest',
        'request',
        'dateResponse',
        'response',
        'originalAltText',
        'generatedAltText',
        'altTextSyncStatus',
    ];

    */
    
    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }
}
