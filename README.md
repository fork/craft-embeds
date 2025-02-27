<div align="center">
  <a href="https://www.fork.de">
    <img src="./assets/fork-logo.png" width="156" height="30" alt="Fork Logo" />
  </a>
</div>

# Embeds plugin for Craft CMS 5.x

NOTE: This Plugin is not meant for actual use with Craft 5. The current release includes a script for migrating
embeds to CKEditor with inline blocks.

## Requirements

This plugin requires Craft CMS 5 or later and the Craft Redactor and CKEditor plugins.
The plugin is not actually working with current Craft CMS 5.0.

## Migrating to CKEditor

In order to migrate your embeds fields to CKEditor fields with inline blocks, do the following steps:

1. Install the CKEditor plugin, if not done already
2. Migrate all your Redactor fields to CKEditor fields and ignore the warnings about missing config settings for the
embeds plugin
3. Check your htmlpurifier config. Remove `"AutoFormat.RemoveEmpty": true,`, if given. This setting causes the purifier
to remove Entry blocks from inside the CKEditor content.
4. Run `php craft gc/run`! The migration script might encounter errors if you don't.
5. In your local environment, run `php craft embeds/migrate-ckeditor [--copy-field=embedsCopy] [--embeds-field=embeds]`.
This might take a while because the script handles every entry, draft and revision with these fields. Run this script
for every combination of Embeds and Embeds Copy field that you have and note every one of those.
6. Adjust your rendering code to the new CKEditor field (See below).
7. Commit and deploy the changes made to the project config. (It should at least add the `createEntry` Button to the
CKEditor config)
8. Run the scripts from steps no. 4 and 5 in every other environment after deploying.
9. You should now have CKEditor fields with your old embeds as inline blocks in the correct position inside the content.
10. After everything is deployed, you can remove the old Embeds Matrix field and the Embeds plugin.

### Rendering with CKEditor

The `mergeEmbeds()` function won't work anymore. And you'll eventually remove this plugin completely. So
rendering is up to you now. The following code example might help you as a starter:

```php
        # $field is your CKEditor field
        $chunks = [];
        foreach ($field->getChunks() as $chunk) {
            if ($chunk instanceof craft\ckeditor\data\Markup) {
                $chunks[] = [
                    'type' => "copy",
                    'html' => $chunk->getHtml(),
                ];
            } else {
                /** @var craft\ckeditor\data\Entry $chunk */
                $embed = $chunk->getEntry();
                $type = rtrim($embed->type->handle);
                $embedData = $this->handleEmbed($embed); // This is where you handle your embeds
                $chunks[] = [
                    'type' => $type,
                    'data' => $embedData,
                ];
            }
        }

        return $chunks;
```

Another approach would be to switch to using
[Partials](https://craftcms.com/docs/5.x/system/elements.html#element-partials) for rendering.

<div align="center">
  <img src="./assets/heart.png" width="38" height="41" alt="Fork Logo" />

  <p>Brought to you by <a href="https://www.fork.de">Fork Unstable Media GmbH</a></p>
</div>
