<?php

namespace fork\embeds\models;

use Craft;
use craft\base\Model;
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
     */
    public $fieldToImageTransformMapping = '';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['fieldToImageTransformMapping', 'array']
        ];
    }
}
