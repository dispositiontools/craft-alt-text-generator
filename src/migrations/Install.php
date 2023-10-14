<?php

namespace dispositiontools\craftalttextgenerator\migrations;

use craft\db\Migration;
use craft\db\Table;
use dispositiontools\craftalttextgenerator\records\AltTextAiApiCall;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Place installation code here...
        
        $this->createTable(
            AltTextAiApiCall::tableName,
            [
                'id' => $this->primaryKey(),
                'requestUserId' => $this->integer()->defaultValue(null),

                'assetId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->defaultValue(null),
                
                // User / On Save / Mass queued
                'requestType' => $this->string(11)->defaultValue(null),
                
                'requestId' => $this->string(128)->defaultValue(null),
                'dateRequest' => $this->dateTime()->defaultValue(null),
                'request' => $this->text(),
                
                'dateResponse' => $this->dateTime()->defaultValue(null),
                'response' => $this->text(),
                
                'originalAltText' => $this->string(512)->defaultValue(null),
                'generatedAltText' => $this->string(512)->defaultValue(null),
                'altTextSyncStatus' => $this->string(10)->defaultValue("pre"),
                
                'dateDeleted' => $this->dateTime()->defaultValue(null),
                
                'humanGeneratedAltText' => $this->string(512)->defaultValue(null),
                'humanAltTextSyncStatus' => $this->string(10)->defaultValue("pre"),
                
                'humanDateRequest' => $this->dateTime()->defaultValue(null),
                'humanRequest' => $this->text(),
                
                'humanDateResponse' => $this->dateTime()->defaultValue(null),
                'humanResponse' => $this->text(),
                
                'humanRequestUserId' => $this->integer()->defaultValue(null),
                
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid()->notNull(),
            ]
        );
        
        
        $this->addForeignKey(
            null,
            AltTextAiApiCall::tableName,
            ['assetId'],
            Table::ELEMENTS,
            ['id'],
            'CASCADE'
        );
        
        $this->addForeignKey(
            null,
            AltTextAiApiCall::tableName,
            ['siteId'],
            Table::SITES,
            ['id'],
            'CASCADE',
            'CASCADE'
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        // Place uninstallation code here...
        $this->dropTableIfExists(AltTextAiApiCall::tableName);
        return true;
    }
}
