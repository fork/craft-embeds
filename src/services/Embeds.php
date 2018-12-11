<?php
/**
 * Embeds Services
 *
 * @link      fork.de
 * @copyright Copyright (c) 2018 Fork
 */

namespace fork\embeds\services;

use Craft;
use craft\base\Component;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\fields\Matrix;
use craft\models\FieldLayout;
use craft\redactor\Field;

/**
 * Embeds Service
 *
 * @author    Fork
 * @package   Embeds
 * @since     1.0.0
 */
class Embeds extends Component
{
    /**
     * @param Entry $entry
     * @return array
     */
    public function getEmbedsForEntry(Entry $entry) : array
    {
        /** @var Field $embedsCopy */
        $embedsCopy = $entry->embedsCopy;
        /** @var Matrix $embeds */
        $embeds = $entry->embeds;

        // Handle copy
        $embedsCopy = str_replace("\n", "", $embedsCopy);
        $embedsCopy = preg_replace('/<p><br \/><\/p>/', '', $embedsCopy);
        $copySegments = [];
        foreach (preg_split('/(<!--pagebreak-->|<hr class=\"redactor_pagebreak\"[^>]*>)/', $embedsCopy) as $segment) {
            $copySegments[] = [
                'type' => "copy",
                'html' => $segment
            ];
        };

        $embedBlocks = [];
        /** @var MatrixBlock $embed */
        foreach ($embeds as $embed) {
            $embedBlocks[] = [
                'type' => $embed->type->handle,
                'data' => $this->getMatrixBlockData($embed)
            ];
        }

        $merged = [];
        for ($i = 0; $i < sizeof($copySegments); $i++) {
            $merged[] = $copySegments[$i];
            if ($i < sizeof($embedBlocks)) {
                $merged[] = $embedBlocks[$i];
            }
        }

        return $merged;
    }

    /**
     * @param MatrixBlock $block
     * @return array
     */
    private function getMatrixBlockData(MatrixBlock $block) :array
    {
        $data = [];
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $block->fieldLayout;
        /** @var \craft\base\Field $field */
        foreach ($fieldLayout->getFields() as $field) {
            $data[$field->handle] = $block[$field->handle];
        }
        return $data;
    }
}
