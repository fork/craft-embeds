<?php
declare(strict_types=1);

namespace fork\embeds\migrations;

use Craft;
use craft\db\Migration;
use craft\fields\Matrix;
use craft\models\MatrixBlockType;
use craft\records\FieldGroup;
use craft\redactor\Field;

/**
 * Install migration.
 */
class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // See if Redactor is installed
        if (!Craft::$app->plugins->isPluginInstalled('redactor')) {
            // If not, try to install it
            try {
                Craft::$app->plugins->installPlugin('redactor');
            } catch (\Throwable $thrwbl) {
                echo "Couldn't install Redactor: " . $thrwbl->getMessage();
                return false;
            }
        }
        // Find the 'Common' field group
        $group = FieldGroup::findOne(['name' => 'Common']);

        // Check if the fields already exist
        $embedsCopy = Craft::$app->fields->getFieldByHandle("embedsCopy");
        $embedsMatrix = Craft::$app->fields->getFieldByHandle("embeds");
        // TODO: Check if only one of the fields exists...
        if ($embedsCopy && $embedsMatrix) {
            return true;
        }
        // Build a Redactor field
        $embedsCopy = new Field([
            "groupId" => $group->id,
            "name" => "Embeds Copy",
            "handle" => "embedsCopy",
            "translationMethod" => "language",
            "instructions" => "Redactor copytext field for the Embeds plugin",
            "redactorConfig" => "Embeds.json",
            "availableVolumes" => [],
            "availableTransforms" => []
        ]);

        // Create a minimal matrix field with the required handle
        $embedsMatrix = new Matrix([
            "groupId" => $group->id,
            "name" => "Embeds",
            "handle" => "embeds",
            "instructions" => "Embeds for the Redactor content"
        ]);

        // Matrix needs at least one blocktype
        $blockType = new MatrixBlockType();
        $blockType->name = 'Image';
        $blockType->handle = 'image';
        $embedsMatrix->setBlockTypes([$blockType]);

        try {
            // Attempt to save the field
            if (!Craft::$app->fields->getFieldByHandle("embedsCopy")) {
                Craft::$app->fields->saveField($embedsCopy);
                if ($embedsCopy->hasErrors()) {
                    Craft::error(implode(', ', $embedsCopy->getErrorSummary(true)), 'embeds-plugin');
                    return false;
                }
            }
            if (!Craft::$app->fields->getFieldByHandle("embeds")) {
                Craft::$app->fields->saveField($embedsMatrix);
                if ($embedsMatrix->hasErrors()) {
                    Craft::error(implode(', ', $embedsMatrix->getErrorSummary(true)), 'embeds-plugin');
                    return false;
                }
            }
        } catch (\Throwable $thrwbl) {
            Craft::error("Couldn't save field:\n" . $thrwbl->getMessage(), 'embeds-plugin');
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        //-- Don't delete anything, too risky! --//
        // Find the embeds matrix
        /** @var Matrix $embedsMatrix */
        //$embedsMatrix = Craft::$app->fields->getFieldByHandle("embeds");
        // If found, Delete it
        //if ($embedsMatrix) {
        //    return (Craft::$app->fields->deleteFieldById($embedsMatrix->id));
        //} else {
        //    return true;
        //}
        return true;
    }
}
