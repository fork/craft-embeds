<?php

/** @noinspection PhpUnused */

namespace fork\embeds\console\controllers;

use Craft;
use craft\ckeditor\Field as CKEditorField;
use craft\ckeditor\Plugin;
use craft\console\Controller;
use craft\elements\db\EntryQuery;
use craft\elements\ElementCollection;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\InvalidFieldException;
use craft\fields\Matrix;
use craft\helpers\FileHelper;
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
    public bool $clearEmbedsField = false;
    public bool $reprocess = false;

    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'copyField';
        $options[] = 'embedsField';
        $options[] = 'entryTypes';
        $options[] = 'clearEmbedsField';
        $options[] = 'reprocess';
        return $options;
    }

    /**
     * Migrate Blocks from Embeds field into the corresponding ckeditor (formerly redactor) field
     * @return int
     * @throws InvalidFieldException
     * @throws Throwable
     * @throws Exception
     */
    public function actionIndex(): int
    {
        /** @var CKEditorField $copyField */
        $copyField = Craft::$app->fields->getFieldByHandle($this->copyField);
        /** @var Matrix $embedsField */
        $embedsField = Craft::$app->fields->getFieldByHandle($this->embedsField);

        if (Craft::$app->config->general->allowAdminChanges) {
            $this->stdout("Updating settings on copy field and it's config...", BaseConsole::FG_CYAN);
            $ckeConfigs = Plugin::getInstance()->ckeConfigs;
            $ckeditorConfig = null;

            if ($copyField->ckeConfig) {
                $ckeditorConfig = $ckeConfigs->getByUid($copyField->ckeConfig);
            }

            // Some projects have the CKEditor field's config unset. In that case, fall back to the first available config.
            if ($ckeditorConfig === null) {
                $allConfigs = $ckeConfigs->getAll();
                if (empty($allConfigs)) {
                    throw new Exception('No CKEditor configs found. Create one in the CP (Settings → CKEditor) and re-run this command.');
                }
                $ckeditorConfig = $allConfigs[0];
                $copyField->ckeConfig = $ckeditorConfig->uid;
            }
            if (!in_array('createEntry', $ckeditorConfig->toolbar)) {
                $ckeditorConfig->toolbar[] = '|';
                $ckeditorConfig->toolbar[] = 'createEntry';
                $ckeConfigs->save($ckeditorConfig);
            }
            $copyField->setEntryTypes($embedsField->getEntryTypes());
            Craft::$app->fields->saveField($copyField);

            $this->stdout(" done." . PHP_EOL, BaseConsole::FG_GREEN);
        }

        foreach ($this->entryTypes($copyField, $embedsField) as $entryType) {
            $this->stdout(sprintf("Migrating Embeds for entries of type %s:" . PHP_EOL, $entryType->name), BaseConsole::FG_CYAN);
            $entries = Entry::find()
                ->type($entryType)
                ->siteId('*')
                ->drafts(null)
                ->revisions(null)
                ->trashed(null)
                ->status(null)
                ->collect();

            foreach ($entries as $i => $entry) {
                $this->stdout(sprintf("\t(%d/%d) Migrating %d %s...", $i + 1, $entries->count(), $entry->id, $entry->title));

                $fieldLayout = $entry->getFieldLayout();

                $copyFieldLayoutElement = $fieldLayout->getFieldByUid($copyField->uid);
                $copyFieldHandle = $copyFieldLayoutElement->handle;

                // Backup the raw (unmodified) copy-field content once per entry, including any pagebreak markers.
                // If a backup exists and reprocess=false (default), skip the entry.
                // If reprocess=true, use the backup as source input so re-running is deterministic.
                $embedsBackupDir = Craft::$app->getPath()->getStoragePath() . DIRECTORY_SEPARATOR . 'embeds';
                FileHelper::createDirectory($embedsBackupDir);
                $backupFilePath = $embedsBackupDir . DIRECTORY_SEPARATOR . sprintf(
                    'entry-%d-site-%d-field-%s-fieldId-%d.txt',
                    $entry->id,
                    $entry->siteId,
                    $copyFieldHandle,
                    $copyField->id
                );

                // Migrate legacy backups (without siteId) to the new filename, so each site gets its own backup.
                $legacyBackupFilePath = $embedsBackupDir . DIRECTORY_SEPARATOR . sprintf(
                    'entry-%d-field-%s-fieldId-%d.txt',
                    $entry->id,
                    $copyFieldHandle,
                    $copyField->id
                );

                if (!file_exists($backupFilePath) && file_exists($legacyBackupFilePath)) {
                    rename($legacyBackupFilePath, $backupFilePath);
                }

                if (file_exists($backupFilePath) && !$this->reprocess) {
                    $this->stdout(" skipped (backup exists)." . PHP_EOL, BaseConsole::FG_YELLOW);
                    continue;
                }

                if (!file_exists($backupFilePath)) {
                    $copy = (string)($entry->getFieldValue($copyFieldHandle) ?? '');
                    file_put_contents($backupFilePath, $copy);
                } else {
                    $backupContents = file_get_contents($backupFilePath);
                    $copy = $backupContents !== false
                        ? $backupContents
                        : (string)($entry->getFieldValue($copyFieldHandle) ?? '');
                }

                $copy = str_replace("\n", "", (string)$copy);
                $copy = preg_replace('/<p><br \/><\/p>/', '', $copy);
                $split = preg_split('/(<!--pagebreak-->|<hr class=\"redactor_pagebreak\"[^>]*>)/', $copy);
                $embedsCount = count($split) - 1;

                /** @var EntryQuery $embedsQuery */
                $embedsQuery = $entry->getFieldValue($fieldLayout->getFieldByUid($embedsField->uid)->handle);
                /** @var ElementCollection<Entry> $embeds */
                $embeds = $embedsQuery->status(null)->fieldId([$embedsField->id, $copyField->id])->collect();

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

                try {
                    if (!Craft::$app->elements->saveElement($entry, false)) {
                        $this->stdout(sprintf("Couldn't save %s", $entry->id), BaseConsole::FG_RED);

                        return ExitCode::UNSPECIFIED_ERROR;
                    }
                } catch (ElementNotFoundException $e) {
                    $this->stdout(sprintf("Couldn't save %s, probably because it is a revision that was deleted in the meantime", $entry->id), BaseConsole::FG_YELLOW);
                }

                $this->stdout(" done." . PHP_EOL, BaseConsole::FG_GREEN);
            }

            $this->stdout(PHP_EOL);
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
