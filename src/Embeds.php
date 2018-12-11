<?php
/**
 * Embeds plugin for Craft CMS 3.x
 *
 * Allow using Embeds within Redactor. Embeds are referenced Matrix Blocks within the Redactor body.
 *
 * @link      https://fork.de
 * @copyright Copyright (c) 2018 Fork Unstable Media GmbH
 */

namespace fork\embeds;

use fork\embeds\services\Embeds as EmbedsService;

use Craft;
use craft\base\Plugin;
use craft\redactor\Field;

use fork\embeds\assetbundles\Embeds\EmbedsAsset;
use yii\base\Event;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
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
 */
class Embeds extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Embeds::$plugin
     *
     * @var Embeds
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0';

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
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        // load custom admin css/js (see site/plugins/embeds/src/assetbundles/embeds/dist)
        if (Craft::$app->request->isCpRequest) {
            $view = Craft::$app->getView();
            $view->registerAssetBundle(EmbedsAsset::class);
        }

        Event::on(
            Field::class,
            Field::EVENT_REGISTER_PLUGIN_PATHS,
            function (Event $event) {
                // add redactor embed plugin assets to load paths
                $event->paths[] = dirname(__DIR__).'/src/assetbundles/embeds/dist/redactor-plugin';
                return $event;
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
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

}
