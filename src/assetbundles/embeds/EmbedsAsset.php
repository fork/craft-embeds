<?php

declare(strict_types=1);
/**
 * Plugin for Craft CMS 3.x
 *
 * Website Features plugin. Do not disable!
 *
 * @link      http://fork.de
 * @copyright Copyright (c) 2018 Fork Unstable Media GmbH
 */

namespace fork\embeds\assetbundles\embeds;

use craft\redactor\assets\redactor\RedactorAsset;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * OttoAsset AssetBundle
 *
 * AssetBundle represents a collection of asset files, such as CSS, JS, images.
 *
 * Each asset bundle has a unique name that globally identifies it among all asset bundles used in an application.
 * The name is the [fully qualified class name](http://php.net/manual/en/language.namespaces.rules.php)
 * of the class representing it.
 *
 * An asset bundle can depend on other asset bundles. When registering an asset bundle
 * with a view, all its dependent asset bundles will be automatically registered.
 *
 * http://www.yiiframework.com/doc-2.0/guide-structure-assets.html
 *
 * @author    Fork Unstable Media GmbH
 * @package   Otto
 * @since     1.0.0
 */
class EmbedsAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * Initializes the bundle.
     */
    public function init()
    {
        // define the path that your publishable resources live
        $this->sourcePath = "@fork/embeds/assetbundles/embeds/dist";

        // define the dependencies
        $this->depends = [
            CpAsset::class,
            RedactorAsset::class,
        ];

        // define the relative path to CSS/JS files that should be registered with the page
        // when this asset bundle is registered
        $this->js = [
            'js/Embeds.js',
        ];

        $this->css = [
            'css/Embeds.css',
        ];

        parent::init();
    }
}
