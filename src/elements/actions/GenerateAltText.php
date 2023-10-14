<?php

namespace dispositiontools\craftalttextgenerator\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\helpers\Queue;
use dispositiontools\craftalttextgenerator\jobs\RequestAltText as RequestAltTextJob;
use dispositiontools\craftalttextgenerator\AltTextGenerator;
/**
 * Generate Alt Text element action
 */
class GenerateAltText extends ElementAction
{
    public static function displayName(): string
    {
        return Craft::t('alt-text-generator', 'Generate Alt Text');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
            (() => {
                new Craft.ElementActionTrigger({
                    type: $type,

                    // Whether this action should be available when multiple elements are selected
                    bulk: true,

                    // Return whether the action should be available depending on which elements are selected
                    validateSelection: (selectedItems) {
                      return true;
                    },

                    // Uncomment if the action should be handled by JavaScript:
                    // activate: () => {
                    //   Craft.elementIndex.setIndexBusy();
                    //   const ids = Craft.elementIndex.getSelectedElementIds();
                    //   // ...
                    //   Craft.elementIndex.setIndexAvailable();
                    // },
                });
            })();
        JS, [static::class]);
            
        return '';
    }

    public function performAction(Craft\elements\db\ElementQueryInterface $query): bool
    {
        $currentUser = Craft::$app->getUser()->getIdentity();
        $numberOfCredits = AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextApiCredits();
        $numberOfCredits = $numberOfCredits - 25;   
        $elements = $query->all();
        $numberOfElements = 0;
        if(is_countable($elements))
        {
            $numberOfElements = count($elements);
        }
        
        if( $numberOfElements > $numberOfCredits   ) 
        {
            $this->setMessage( 'Your alttext.io account only has about '.$numberOfCredits . " and you have selected ". $numberOfElements . " elements. Please reduce the number of assets selected and try again.");
            return false;
        }
        
        $returnMessage = "";
        foreach ($elements as $element) {
            
            Queue::push(new RequestAltTextJob([
                "assetId" => $element->id,
                "requestUserId" => $currentUser->id,
            ]));
        }
        /**/
        
            $this->setMessage( $numberOfElements. " elements have been queued with alttext.ai for alt text generation.");
        
        return true;
        
        // ...
    }
}
