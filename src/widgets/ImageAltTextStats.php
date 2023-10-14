<?php

namespace dispositiontools\craftalttextgenerator\widgets;

use Craft;
use craft\base\Widget;
use dispositiontools\craftalttextgenerator\AltTextGenerator;

/**
 * Image Alt Text Stats widget type
 */
class ImageAltTextStats extends Widget
{
    public static function displayName(): string
    {
        return Craft::t('alt-text-generator', 'Image Alt Text Stats');
    }

    public static function isSelectable(): bool
    {
        return true;
    }

    public static function icon(): ?string
    {
        return null;
    }

    public function getBodyHtml(): ?string
    {
        $stats = AltTextGenerator::getInstance()->altTextAiApi->statsImagesWithAltText();
        $numberOfAltTextsToReview = AltTextGenerator::getInstance()->altTextAiApi->getNumberOfAltTextsToReview();
        // todo: replace with custom body HTML
        $html = "<p>";
        
        $html .= "Images with alt text: " . $stats['imagesWithAltText'];
        $html .= "<br />";
        $html .= "Images without alt text: " . $stats['imagesWithoutAltText'];
        $html .= "</p>";
        
        
        $html .= "<p>";
        $html .= "Number of generated Alt Texts to review: " . $numberOfAltTextsToReview;
        $html .= "</p>";
        
        if ($numberOfAltTextsToReview > 0) {
        }
      
        return $html;
    }
}
