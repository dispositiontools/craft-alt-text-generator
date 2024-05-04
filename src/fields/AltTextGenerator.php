<?php

namespace dispositiontools\craftalttextgenerator\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\elements\db\ElementQueryInterface;
use craft\fields\conditions\TextFieldConditionRule;
use craft\helpers\Html;
use craft\helpers\StringHelper;
use yii\db\Schema;

/**
 * Alt Text Generator field type
 */
class AltTextGenerator extends Field implements PreviewableFieldInterface
{
    public static function displayName(): string
    {
        return Craft::t('alt-text-generator', 'Alt Text Generator');
    }

    public static function phpType(): string
    {
        return 'mixed';
    }

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            // ...
        ]);
    }

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            // ...
        ]);
    }

    public function getSettingsHtml(): ?string
    {
        return null;
    }

    public function getContentColumnType(): array|string
    {
        return Schema::TYPE_STRING;
    }

    public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element = null): mixed
    {
        return $value;
    }

    protected function inputHtml(mixed $value, ElementInterface $element = null, bool $inline = false): string
    {
        return Html::textinput($this->handle, $value, false);
    }

    public function getElementValidationRules(): array
    {
        return [];
    }

    protected function searchKeywords(mixed $value, ElementInterface $element): string
    {
        return StringHelper::toString($value, ' ');
    }

    public function getElementConditionRuleType(): array|string|null
    {
        return TextFieldConditionRule::class;
    }


}
