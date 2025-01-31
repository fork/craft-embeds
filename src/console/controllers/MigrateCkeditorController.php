<?php

/** @noinspection PhpUnused */

namespace fork\embeds\console\controllers;

use Craft;
use craft\ckeditor\Field as CKEditorField;
use craft\ckeditor\Plugin;
use craft\console\Controller;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use craft\models\EntryType;
use Illuminate\Support\Collection;
use Throwable;
use yii\base\Exception;
use yii\console\Exception as ConsoleException;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

/**
 * Migrate Ckeditor controller
 */
class MigrateCkeditorController extends Controller
{
    public $defaultAction = 'index';
    public string $copyField = 'embedsCopy';
    public string $embedsField = 'embeds';
    public string|null $entryTypes = null;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'copyField';
        $options[] = 'embedsField';
        $options[] = 'entryTypes';
        return $options;
    }

    /**
     * Migrate Blocks from Embeds field into the corresponding ckeditor (formerly redactor) field
     * @return int
     * @throws InvalidFieldException
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function actionIndex(): int
    {
        /** @var CKEditorField $copyField */
        $copyField = Craft::$app->fields->getFieldByHandle($this->copyField);
        /** @var Matrix $embedsField */
        $embedsField = Craft::$app->fields->getFieldByHandle($this->embedsField);

        if (Craft::$app->config->general->allowAdminChanges) {
            $this->output("Updating settings on copy field and it's config.");
            $ckeditorConfig = Plugin::getInstance()->ckeConfigs->getByUid($copyField->ckeConfig);
            if (!in_array('createEntry', $ckeditorConfig->toolbar)) {
                $ckeditorConfig->toolbar[] = '|';
                $ckeditorConfig->toolbar[] = 'createEntry';
                Plugin::getInstance()->ckeConfigs->save($ckeditorConfig);
            }
            $copyField->setEntryTypes($embedsField->getEntryTypes());
            Craft::$app->fields->saveField($copyField);
        }

        foreach ($this->entryTypes($copyField, $embedsField) as $entryType) {
            $this->output(sprintf("Migrating Embeds for entries of type %s:\n", $entryType->name));
            $entries = Entry::find()
                ->type($entryType)
                ->siteId('*')
                ->status(null)
                ->collect();

            foreach ($entries as $i => $entry) {
                $this->output(sprintf("\t(%d/%d) Migrating %8d %s...", $i + 1, $entries->count(), $entry->id, $entry->title));

                $fieldLayout = $entry->getFieldLayout();

                $copy = $entry->getFieldValue($fieldLayout->getFieldByUid($copyField->uid)->handle);
                $copy = str_replace("\n", "", $copy);
                $copy = preg_replace('/<p><br \/><\/p>/', '', $copy);
                $split = preg_split('/(<!--pagebreak-->|<hr class=\"redactor_pagebreak\"[^>]*>)/', $copy);
                $embedsCount = count($split) - 1;

                /** @var ElementCollection<Matrix> $embeds */
                $embeds = $entry->getFieldValue($fieldLayout->getFieldByUid($embedsField->uid)->handle)->status(null)->collect();
                $enabledEmbedsCount = 0;
                $embeds = $embeds->each(
                    function(Entry $embed) use ($copyField, $embedsCount, &$enabledEmbedsCount) {
                        $embed->fieldId = $copyField->id;
                        if ($enabledEmbedsCount >= $embedsCount) {
                            // superfluous embeds still get added to the ckeditor field, but disabled
                            $embed->enabled = false;
                        }
                        if ($embed->enabled) {
                            $enabledEmbedsCount++;
                        }
                        if (!Craft::$app->elements->saveElement($embed, false)) {
                            throw new ConsoleException("Couldn't update fieldId on embed %s\n", $embed->id);
                        }
                    }
                );

                $merged = collect();
                foreach ($split as $textblock) {
                    $merged->add($textblock);
                    $embedsToInsert = $embeds->takeUntil(function(Entry $embed) {
                        return $embed->enabled;
                    });
                    $embedsToInsert = $embeds->splice(0, $embedsToInsert->count() + 1)
                        ->map(function(Entry $embed) {
                            return sprintf('<craft-entry data-entry-id="%s"></craft-entry>', $embed->id);
                        });
                    $merged = $merged->merge($embedsToInsert);
                }

                $entry->setFieldValue($copyField->handle, $merged->implode(''));
                $entry->setFieldValue($embedsField->handle, []);

                if (!Craft::$app->elements->saveElement($entry, false)) {
                    $this->output(sprintf("Couldn't save %s\n", $entry->id));

                    return ExitCode::UNSPECIFIED_ERROR;
                }

                $this->output("\tdone.", BaseConsole::FG_GREEN);
            }
            $this->output("\n");
        }

        return ExitCode::OK;
    }

    /**
     * @return Collection<EntryType>
     */
    private function entryTypes(CKEditorField $copyField, Matrix $embedsField): Collection
    {
        if (!empty($this->entryTypes)) {
            $entryTypes = collect(explode(",", $this->entryTypes))
                ->map(function($entryType) {
                    return Craft::$app->getEntries()->getEntryTypeByHandle(trim($entryType));
                })
                ->filter()
                ->values();
        } else {
            $entryTypes = collect(Craft::$app->getEntries()->getAllEntryTypes())
                ->filter(function(EntryType $entryType) use ($embedsField, $copyField) {
                    $fieldLayout = $entryType->getFieldLayout();

                    return $fieldLayout->getFieldByUid($embedsField->uid) && $fieldLayout->getFieldByUid($copyField->uid);
                });
        }

        return $entryTypes;
    }
}
