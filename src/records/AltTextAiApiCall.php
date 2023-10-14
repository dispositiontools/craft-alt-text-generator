<?php

namespace dispositiontools\craftalttextgenerator\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

/**
 * Alt Text Ai Api Call record
 */
class AltTextAiApiCall extends ActiveRecord
{
    use SoftDeleteTrait;
    
    public const tableName = '{{%alt-text-generator_alttextaiapicalls}}';

    public static function tableName(): string
    {
        return '{{%alt-text-generator_alttextaiapicalls}}';
    }
}
