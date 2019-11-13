<?php

namespace fork\embeds\migrations;

use Craft;
use craft\db\Migration;
use craft\fields\Matrix;
use craft\records\FieldGroup;
use craft\redactor\Field;
use fork\embeds\Embeds;
use yii\base\Exception;

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
        try {
            // Attempt to save the field
            if (Craft::$app->fields->saveField($embedsCopy)) {
                // Create a minimal matrix field with the required handle
                $embedsMatrix = new Matrix([
                    "groupId" => $group->id,
                    "name" => "Embeds",
                    "handle" => "embeds",
                    "instructions" => "Embeds for the Redactor content"
                ]);
                // Attempt to save the field
                if(Craft::$app->fields->saveField($embedsMatrix)) {
                    return true;
                } else {
                    echo "Couldn't save embeds matrix!";
                    return false;
                }
            } else {
                echo "Couldn't save embeds copy!";
                return false;
            }
        } catch (\Throwable $thrwbl) {
            echo "Couldn't save field:\n" . $thrwbl->getMessage();
            return false;
        }
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
