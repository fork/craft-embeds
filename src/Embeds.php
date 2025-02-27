<?php

declare(strict_types=1);
/**
 * Embeds plugin for Craft CMS 3.x and 4.x
 *
 * Allow using Embeds within Redactor. Embeds are referenced Matrix Blocks within the Redactor body.
 *
 * @link      https://fork.de
 * @copyright Copyright (c) 2018 Fork Unstable Media GmbH
 */

namespace fork\embeds;

use Craft;
use craft\base\Model;
use craft\base\Plugin;
use craft\redactor\events\RegisterPluginPathsEvent;
use craft\redactor\Field as RedactorField;
use craft\web\twig\variables\CraftVariable;
use fork\embeds\assetbundles\embeds\EmbedsAsset;
use fork\embeds\models\Settings;
use fork\embeds\services\Embeds as EmbedsService;
use fork\embeds\variables\EmbedsVariable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. Little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 *
 * @author    Fork Unstable Media GmbH
 * @package   Embeds
 * @since     1.0.0
 *
 * @property  EmbedsService $embeds
 * @property  Settings $settings
 * @method    Settings getSettings()
 */
class Embeds extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Embeds::$plugin
     */
    public static self $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     */
    public string $schemaVersion = '1.0.0';

    public ?string $developer = 'Fork Unstable Media GmbH';

    public ?string $developerUrl = 'https://fork.de';

    public ?string $documentationUrl = 'https://github.com/fork/craft-embeds';

    public ?string $changelogUrl = 'https://github.com/fork/craft-embeds/blob/master/CHANGELOG.md';

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Embeds::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        // load custom admin css/js (see site/plugins/embeds/src/assetbundles/embeds/dist)
        if (Craft::$app->request->isCpRequest) {
            $view = Craft::$app->getView();
            $view->registerAssetBundle(EmbedsAsset::class);

            $settings = $this->getSettings();
            $embedsName = $settings->embedsFieldName;
            $embedsCopyName = $settings->embedsCopyFieldName;

            $view->registerJs("
            if (typeof \$R !== 'undefined') {
                setTimeout(function() {
                  // get all editors with embeds fields
                  Craft.initEmbeds('$embedsName', '$embedsCopyName');
                }, 500);
            }");
        }

        Event::on(
            RedactorField::class,
            RedactorField::EVENT_REGISTER_PLUGIN_PATHS,
            function(RegisterPluginPathsEvent $event) {
                // add redactor embed plugin assets to load paths
                $event->paths[] = dirname(__DIR__) . '/src/assetbundles/embeds/dist/redactor-plugin';

                return $event;
            }
        );

        // Register our variables
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('embeds', EmbedsVariable::class);
            }
        );

        /**
         * Logging in Craft involves using one of the following methods:
         *
         * Craft::trace(): record a message to trace how a piece of code runs. This is mainly for development use.
         * Craft::info(): record a message that conveys some useful information.
         * Craft::warning(): record a warning message that indicates something unexpected has happened.
         * Craft::error(): record a fatal error that should be investigated as soon as possible.
         *
         * Unless `devMode` is on, only Craft::warning() & Craft::error() will log to `craft/storage/logs/web.log`
         *
         * It's recommended that you pass in the magic constant `__METHOD__` as the second parameter, which sets
         * the category to the method (prefixed with the fully qualified class name) where the constant appears.
         *
         * To enable the Yii debug toolbar, go to your user account in the AdminCP and check the
         * [] Show the debug toolbar on the front end & [] Show the debug toolbar on the Control Panel
         *
         * http://www.yiiframework.com/doc-2.0/guide-runtime-logging.html
         */
        Craft::info(
            Craft::t(
                'embeds',
                '{name} plugin loaded',
                [
                    'name' => $this->name,
                ]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    // Protected Methods
    // =========================================================================

    /**
     * Creates and returns the model used to store the plugin’s settings.
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * Returns the rendered settings HTML, which will be inserted into the content
     * block on the settings page.
     *
     * @return string The rendered settings HTML
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate(
            'embeds/settings',
            [
                'settings' => $this->getSettings(),
            ]
        );
    }
}
