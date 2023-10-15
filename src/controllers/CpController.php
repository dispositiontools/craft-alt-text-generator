<?php

namespace dispositiontools\craftalttextgenerator\controllers;

use Craft;
use craft\web\Controller;
use dispositiontools\craftalttextgenerator\AltTextGenerator;

use yii\web\Response;

/**
 * Cp controller
 */
class CpController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = self::ALLOW_ANONYMOUS_NEVER;

    
    /**
     * alt-text-generator/cp/dashboard action
     */
    public function actionDashboard(): Response
    {
        // ...
        $this->requirePermission('altTextGeneratorViewDashboard');
        
        $request = Craft::$app->getRequest();
            
       
            
        $settings = AltTextGenerator::getInstance()->getSettings();
        if(! $settings->apiKey)
        {
            return $this->renderTemplate('alt-text-generator/_cp/setup', ['title' => 'Alt Text Generator']);
        }

        $apiCreditsCount = AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextApiCredits();
    
        // We need these three request parameters for the view. ("value" optional)
        $templateParams = [
            'title' => 'Alt Text Generator',
            'settings' => $settings,
            'credits' => $apiCreditsCount,
            'apiCalls' => AltTextGenerator::getInstance()->altTextAiApi->getApiCalls([
                'where' =>
                [
                    'altTextSyncStatus' => ['review'],
                ],
            ]),
        ];
        return $this->renderTemplate('alt-text-generator/_cp/dashboard', $templateParams);
    }
    
    
    
    /**
     * alt-text-generator/cp/history action
     */
    public function actionHistory(): Response
    {
        // ...
        $this->requirePermission('altTextGeneratorViewHistory');
        $request = Craft::$app->getRequest();
            
        $settings = AltTextGenerator::getInstance()->getSettings();
        
    
        // We need these three request parameters for the view. ("value" optional)
        $templateParams = [
            'title' => 'Alt Text Generator',
            'settings' => $settings,
            'apiCalls' => AltTextGenerator::getInstance()->altTextAiApi->getApiCalls([]),
        ];
        return $this->renderTemplate('alt-text-generator/_cp/history', $templateParams);
    }
    
    
    /**
     * alt-text-generator/cp/queue-images action
    */
    public function actionQueueImages(): Response
    {
        $generateForNoAltText = false;
        $generateForAltText = false;
        
        AltTextGenerator::getInstance()->altTextAiApi->queueAllImages($generateForNoAltText,$generateForAltText);
            
            
        return $this->redirectToPostedUrl();
    }
    
    
    /**
     * alt-text-generator/cp/refresh-api-token-count action
    */
    public function actionRefreshApiTokenCount(): Response
    {
        AltTextGenerator::getInstance()->altTextAiApi->refreshNumberOfAltTextApiCredits();
        return $this->redirectToPostedUrl();
    }
    
    /**
     * alt-text-generator/cp/update-api-calls action
    */
    public function actionUpdateApiCalls(): Response
    {
        $request = Craft::$app->getRequest();
        
        $assets = $request->post('assets');
        
        AltTextGenerator::getInstance()->altTextAiApi->updateApiCalls($assets);
            
        // Go through each type of edit and get it done.
        return $this->redirectToPostedUrl();
    }
    
    
    
    /**
     * alt-text-generator/cp/update-api-calls action
    */
    public function actionApiCalls(): Response
    {
        // Go through each type of edit and get it done.
        return $this->redirectToPostedUrl();
    }
}
