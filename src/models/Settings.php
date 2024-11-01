<?php
declare(strict_types=1);

namespace fork\embeds\models;

use Craft;
use craft\base\Model;

/**
 * Class Settings
 * @package fork\embeds\models
 * @since 1.0.1
 */
class Settings extends Model
{
    /**
     * The name of the redactor copytext field
     *
     * @var string
     */
    public $embedsCopyFieldName = 'embedsCopy';

    /**
     * The name of the embeds matrix field
     *
     * @var string
     */
    public $embedsFieldName = 'embeds';

    /**
     * The format to use for date fields
     *
     * @var string
     */
    public $dateFormat = 'default';

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['embedsCopyFieldName', 'string'],
            ['embedsFieldName', 'string'],
            ['dateFormat', 'string'],
        ];
    }
}
