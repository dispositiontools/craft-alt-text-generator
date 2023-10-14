<?php

namespace dispositiontools\craftalttextgenerator\console\controllers;

use craft\console\Controller;
use dispositiontools\craftalttextgenerator\AltTextGenerator;
use yii\console\ExitCode;

/**
 * Tools controller
 */
class ToolsController extends Controller
{
    public $defaultAction = 'index';

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        switch ($actionID) {
            case 'index':
                // $options[] = '...';
                break;
        }
        return $options;
    }

    /**
     * alt-text-generator/tools command
     */
    public function actionIndex(): int
    {
        // ...
        echo "Tools. ";
        echo "\n";
        return ExitCode::OK;
    }
    
    
    /**
     * alt-text-generator/tools/get-alt-text-for-url command
     */
    public function actionGetAltTextForUrl($url): int
    {
        // ...
        echo "Get alt text for url. ";
        echo "\n";
        echo "Url: " . $url;
        echo "\n";
        
        $response = AltTextGenerator::getInstance()->altTextAiApi->getAltTextForUrl($url);
            
        echo $response;
        
        echo "\n";
        return ExitCode::OK;
    }
    
    /**
     * alt-text-generator/tools/queue-all-images command
     */
    public function actionQueueAllImages(): int
    {
        // ...
        echo "Queue all images which don't have any alt text ";
        echo "\n";

        echo "\n";
        
        AltTextGenerator::getInstance()->altTextAiApi->queueAllImages();
                    
        echo "\n";
        return ExitCode::OK;
    }
    
    /**
     * alt-text-generator/tools/get-number-of-api-credits
     */
    public function actionGetNumberOfApiCredits(): int
    {
        echo "Getting number of api credts...";
        echo "\n";
        $response = AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextApiCredits();
        echo "\n";
        echo "Number of credits: ";
        echo $response;
        echo "\n";
        echo "\n";
        return ExitCode::OK;
    }
    
    /**
     * alt-text-generator/tools/get-generated-alt-text-for-asset 12463
     */
    public function actionGetGeneratedAltTextForAsset($assetId): int
    {
        AltTextGenerator::getInstance()->altTextAiApi->callAltTextAiAipi($assetId);
        echo "\n";
        echo "\n";
        return ExitCode::OK;
    }
    
    /**
     * alt-text-generator/tools/check-get-api-call-by-id 25
     */
    public function actionCheckGetApiCallById($id)
    {
        AltTextGenerator::getInstance()->altTextAiApi->checkGetApiCallById($id);
            
        echo "\n";
        echo "\n";
        return ExitCode::OK;
    }
    
    /**
     * alt-text-generator/tools/request-human-review 1529
     */
    public function actionRequestHumanReview($apiCallId)
    {
        AltTextGenerator::getInstance()->altTextAiApi->callAltTextAiHumanReviewAipi($apiCallId);
            
        echo "\n";
        echo "\n";
        return ExitCode::OK;
    }
    
    /**
     * alt-text-generator/tools/refresh-image-details 1525
     */
    public function actionRefreshImageDetails($apiCallId)
    {
        
        echo "Getting image details for ".$apiCallId;
        echo "\n";
        AltTextGenerator::getInstance()->altTextAiApi->refreshImageDetailsFromAltTextAi( $apiCallId );            
        echo "\n";
        echo "\n";
        return ExitCode::OK;
    }
}
