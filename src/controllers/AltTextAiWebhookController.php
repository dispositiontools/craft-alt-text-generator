<?php

namespace dispositiontools\craftalttextgenerator\controllers;

use Craft;
use craft\web\Controller;
use dispositiontools\craftalttextgenerator\AltTextGenerator;
use yii\web\Response;

/**
 * Alt Text Ai Webhook controller
 */
class AltTextAiWebhookController extends Controller
{
    public $defaultAction = 'index';
    protected array|int|bool $allowAnonymous = ['web-hook'];
    public $enableCsrfValidation = false;
    
    /*
    public function beforeAction($actionId): bool
    {
        // Don’t require a CSRF token for incoming webhooks:
        if ($actionId === 'web-hook') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($actionId);
    }
    */

    /**
     * alt-text-generator/alt-text-ai-webhook action
     */
    public function actionIndex(): Response
    {
        // ...
        return $this->renderTemplate('alt-text-generator/_cp/setup', ['title' => 'Alt Text Generator']);
    }
    
    
    
    /**
     * alt-text-generator/alt-text-ai-webhook/web-hook action
     */
    public function actionWebHook(): Response
    {
        // ...
        $this->requirePostRequest();
        
        $request = Craft::$app->getRequest();
        $body = $request->getRawBody();
            
        file_put_contents("raw-body-" . date("Ymd_His") . ".json",  $body);
       
        $settings = AltTextGenerator::getInstance()->getSettings();
        
        $securityCode = $request->getQueryParam('securityCode');
        
        if ($securityCode != $settings['securityCode']) {
            return $this->asJson(['success' => false ]);
        }

        AltTextGenerator::getInstance()->altTextAiApi->processAltTextAiWebhook($body);
            
        return $this->asJson(['success' => true ]);
    }
}
