<?php

namespace fork\embeds\migrations;

use Craft;
use craft\db\Migration;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): void
    {
        Craft::error("This plugin is not meant to be freshly installed anymore, only for legacy usage and eventual migration to CKEditor.");
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //-- Don't delete anything, too risky! --//
        return true;
    }
}
