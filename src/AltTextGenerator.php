<?php

namespace dispositiontools\craftalttextgenerator;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\events\ModelEvent;
use craft\events\PluginEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\helpers\Queue;
use craft\services\Dashboard;
use craft\services\Fields;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\services\Utilities;
use craft\web\UrlManager;
use dispositiontools\craftalttextgenerator\elements\actions\GenerateAltText;
use dispositiontools\craftalttextgenerator\fields\AltTextGenerator as AltTextGeneratorAlias;
use dispositiontools\craftalttextgenerator\jobs\RequestAltText as RequestAltTextJob;
use dispositiontools\craftalttextgenerator\models\Settings;
use dispositiontools\craftalttextgenerator\services\AltTextAiApi;
use dispositiontools\craftalttextgenerator\utilities\AltTextGeneratorUtility;


use dispositiontools\craftalttextgenerator\widgets\ImageAltTextStats;
use yii\base\Event;

/**
 * Alt text Generator plugin
 *
 * @method static AltTextGenerator getInstance()
 * @method Settings getSettings()
 * @author Disposition Tools <support@disposition.tools>
 * @copyright Disposition Tools
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read AltTextAiApi $altTextAiApi
 */
class AltTextGenerator extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;
    public bool $hasCpSection = true;


    public static function config(): array
    {
        return [
            'components' => ['altTextAiApi' => AltTextAiApi::class],
        ];
    }

    public function init(): void
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function() {
            $this->attachEventHandlers();
            // ...
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('alt-text-generator/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        // Register event handlers here ...
        // (see https://craftcms.com/docs/4.x/extend/events.html to get started)
            
        /*
        Event::on(Fields::class, Fields::EVENT_REGISTER_FIELD_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = AltTextGeneratorAlias::class;
        });
        */
        


        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function(RegisterComponentTypesEvent $event) {
            $event->types[] = AltTextGeneratorUtility::class;
        });
 
        if (Craft::$app->user->checkPermission('altTextGeneratorAssetAction')) {
            Event::on(
                    Asset::class,
                    Element::EVENT_REGISTER_ACTIONS,
                    function(RegisterElementActionsEvent $event) {
                        $event->actions[] = GenerateAltText::class;
                    }
                );
        }
        
        
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => 'Alt Text Generator',
                    'permissions' => [
                        
                        'altTextGeneratorWidgetStats' => [
                            'label' => 'Stats widget',
                        ],
                        'altTextGeneratorViewDashboard' => [
                            'label' => 'View dashboard',
                            'nested' => [
                                'altTextGeneratorSyncAltText' => [
                                    'label' => 'Sync generated alt text with asset',
                                ],
                                'altTextGeneratorHumanReview' => [
                                    'label' => 'Request human review of AI generated alt text',
                                ],
                                'altTextGeneratorDelete' => [
                                    'label' => 'Delete record',
                                ],
                                'altTextGeneratorRequestRefresh' => [
                                    'label' => 'Refresh image data from altText.ai',
                                ]
                            ],
                        ],
                        'altTextGeneratorViewHistory' => [
                                'label' => 'View history',
                            ],
                    
                        'altTextGeneratorAssetAction' => [
                            'label' => 'Queue alt text generation with asset actions',
                        ],
                   ],
                ];
            }
        );
       
       
      
        Event::on(
            Asset::class,
            Asset::EVENT_AFTER_PROPAGATE,
            function(ModelEvent $event) {
                if (($event->sender->enabled && $event->sender->getEnabledForSite()) &&
                    $event->sender->firstSave && $event->sender->alt == "") {
                    $currentUser = Craft::$app->getUser()->getIdentity();
                    if ($currentUser) {
                        $currentUserId = $currentUser->id;
                    } else {
                        $currentUserId = null;
                    }
                 
                    Queue::push(new RequestAltTextJob([
                        "assetId" => $event->sender->id,
                        "requestUserId" => $currentUserId,
                        "actionType" => "On save",
                    ]));
                }
            }
        );
        /**/
        
        
        // Register the control panel routes.
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['alt-text-generator'] = 'alt-text-generator/cp/dashboard';
            $event->rules['alt-text-generator/history'] = 'alt-text-generator/cp/history';
        });
        
        // register event for when saving an asset
        
        
        
        
        // Generate the encryption key that is unique to this installation.
        Event::on(Plugins::class, Plugins::EVENT_AFTER_INSTALL_PLUGIN, function(PluginEvent $event) {
            if ($event->plugin === $this) {
                $initialSettings = ['securityKey' => Craft::$app->getSecurity()->generateRandomString(16)];
                Craft::$app->getPlugins()->savePluginSettings($this, $initialSettings);
            }
        });
        
        Event::on(Dashboard::class, Dashboard::EVENT_REGISTER_WIDGET_TYPES, function(RegisterComponentTypesEvent $event) {
            if (Craft::$app->user->checkPermission('altTextGeneratorWidgetStats')) {
                $event->types[] = ImageAltTextStats::class;
            }
        });
    }
    
         
    public function getCpNavItem(): ?array
    {
        $numberOfItemsToReview = AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextsToReview();
        
        
        $item = parent::getCpNavItem();
        $item['badgeCount'] = $numberOfItemsToReview;
        $showNav = false;
        if (Craft::$app->user->checkPermission('altTextGeneratorViewDashboard')) {
            $item['subnav']['review'] = ['label' => 'Review', 'url' => 'alt-text-generator'];
            $showNav = true;
        }
        if (Craft::$app->user->checkPermission('altTextGeneratorViewHistory')) {
            $item['subnav'][ 'history'] = ['label' => 'History', 'url' => 'alt-text-generator/history'];
            $showNav = true;
        }
        if ($showNav == true) {
            return $item;
        } else {
            return null;
        }
    }
}
