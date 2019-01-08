<?php

namespace fork\embeds\models;

use Craft;
use craft\base\Model;
use craft\validators\ArrayValidator;
use fork\embeds\Embeds;

/**
 * Class Settings
 * @package fork\embeds\models
 * @since 1.0.1
 */
class Settings extends Model
{
    /**
     * @var array $fieldToImageTransformMapping
     *
     * This is an array which will be populated like this:
     * [
     *      [
     *          'fieldID' => fieldId1,
     *          'transforms' => [
     *              [
     *                  'srcset' => "",
     *                  'transform' => transformId1,
     *                  'suffix' => ""
     *              ],
     *              [
     *                  'srcset' => "",
     *                  'transform' => transformId2,
     *                  'suffix' => ""
     *              ]
     *          ]
     *      ],
     *      [
     *          'fieldID' => fieldId2,
     *          'transforms' => [
     *              [
     *                  'srcset' => "",
     *                  'transform' => transformId1,
     *                  'suffix' => ""
     *              ]
     *          ]
     *      ]
     * ]
     */
    public $fieldToImageTransformMapping = [];

    /**
     * @param $fieldId
     * @return array
     */
    public function getSettingsByFieldId($fieldId) : array
    {
        foreach ($this->fieldToImageTransformMapping as $fieldSettings) {
            if ($fieldId == $fieldSettings['fieldId']) {
                return $fieldSettings;
            }
        }
        return [];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['fieldToImageTransformMapping', ArrayValidator::class]
        ];
    }
}
