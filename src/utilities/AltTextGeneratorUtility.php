<?php

namespace dispositiontools\craftalttextgenerator\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\UrlHelper;
use dispositiontools\craftalttextgenerator\AltTextGenerator;

/**
 * Alt Text Generator Utility utility
 */
class AltTextGeneratorUtility extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('alt-text-generator', 'Alt Text Generator Utility');
    }

    public static function id(): string
    {
        return 'alt-text-generator-utility';
    }

    public static function iconPath(): ?string
    {
        
        // Set the icon mask path
        $iconPath = Craft::getAlias('@vendor/dispositiontools/craft-alt-text-generator/src/icon-mask.svg');
        
        // If not a string, bail
        if (!is_string($iconPath)) {
            return null;
        }
        
        // Return the icon mask path
        return $iconPath;
    }

    public static function contentHtml(): string
    {
        $view = Craft::$app->getView();
        
        // $view->registerAssetBundle(DbBackupAsset::class);
        //$view->registerJs('new Craft.DbBackupUtility(\'db-backup\');');
        
        $settings = AltTextGenerator::getInstance()->getSettings();
            
        if (!$settings->securityCode) {
            $settings['securityKey'] = Craft::$app->getSecurity()->generateRandomString(16);
            Craft::$app->getPlugins()->savePluginSettings(AltTextGenerator , $settings);
        }
        
        $webHookParams = [
            'securityCode' => $settings->securityCode,
        ];
        $webhookUrl = UrlHelper::actionUrl('alt-text-generator/alt-text-ai-webhook/web-hook', $webHookParams, null, false);
        
        $apiCreditsCount = AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextApiCredits();
        $variables = [
            'apiCreditsCount' => $apiCreditsCount,
            'webhookUrl' => $webhookUrl,
        ];
        
        return $view->renderTemplate('alt-text-generator/_components/utilities/alt-text-utilities.twig', $variables);
    }
}
