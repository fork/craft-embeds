<?php
declare(strict_types=1);
/**
 * Embeds plugin for Craft CMS 3.x
 *
 * Embeds Website Features plugin. Do not disable!
 *
 * @link      http://fork.de
 * @copyright Copyright (c) 2018 Fork Unstable Media GmbH
 */

namespace fork\embeds\variables;

use craft\base\Element;
use fork\embeds\Embeds;


/**
 * Embeds Variable
 *
 * Craft allows plugins to provide their own template variables, accessible from
 * the {{ craft }} global variable (e.g. {{ craft.embeds }}).
 *
 * https://craftcms.com/docs/plugins/variables
 *
 * @author    Fork Unstable Media GmbH
 * @package   Embeds
 * @since     1.0.0
 */
class EmbedsVariable
{
    // Public Methods
    // =========================================================================

  /**
   * Whatever you want to output to a Twig template can go into a Variable method.
   * You can have as many variable functions as you want.  From any Twig template,
   * call it like this:
   *
   *     {{ craft.embeds.exampleVariable }}
   *
   * Or, if your variable requires parameters from Twig:
   *
   *     {{ craft.embeds.exampleVariable(twigValue) }}
   *
   * @param Element $element
   * @param array $ignoreFields
   * @param int $nestingLevel
   * @return array
   * @throws \yii\base\InvalidConfigException
   */
    public function getElementData(Element $element, array $ignoreFields = [], int $nestingLevel = 0): array
    {
      return Embeds::$plugin->embeds->getElementData($element, $ignoreFields, $nestingLevel);
    }
}
