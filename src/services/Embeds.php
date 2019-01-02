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
    public function getEmbedsForEntry(Entry $entry): array
    {
        /** @var Field $embedsCopy */
        $embedsCopy = $entry->embedsCopy;
        /** @var MatrixBlock[] $embeds */
        $embeds = $entry->embeds->all();

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
            $type = $embed->type->handle;
            $embedBlocks[] = [
                'type' => $type,
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
    private function getMatrixBlockData(MatrixBlock $block): array
    {
        $data = [];
        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $block->fieldLayout;
        /** @var \craft\base\Field $field */
        foreach ($fieldLayout->getFields() as $field) {
            switch (get_class($field)) {
                case "craft\\fields\\Assets":
                    $func = function ($x) {
                        return [
                            'id' => $x->id,
                            'url' => $x->url,
                            'height' => $x->height,
                            'width' => $x->width,
                            'status' => $x->status,
                        ];
                    };
                    $data[$field->handle] = array_map($func, $block[$field->handle]->all());
                    break;
                case "craft\\fields\\Categories":
                    $func = function ($x) {
                        return [
                            'id' => $x->id,
                            'title' => $x->title,
                            'slug' => $x->slug,
                            'status' => $x->status,
                        ];
                    };
                    $data[$field->handle] = array_map($func, $block[$field->handle]->all());
                    break;

                case "craft\\fields\\Entries":
                    $func = function ($x) {
                        return [
                            'id' => $x->id,
                            'title' => $x->title,
                            'slug' => $x->slug,
                            'uri' => $x->uri,
                            'authorId' => $x->authorId,
                            'postDate' => $x->postDate,
                            'sectionId' => $x->sectionId,
                            'dateCreated' => $x->dateCreated,
                            'dateUpdated' => $x->dateUpdated,
                        ];
                    };
                    $data[$field->handle] = array_map($func, $block[$field->handle]->all());
                    break;
                case "craft\\fields\\Tags":
                    $func = function ($x) {
                        return [
                            'id' => $x->id,
                            'title' => $x->title,
                            'slug' => $x->slug,
                            'status' => $x->status
                        ];
                    };
                    $data[$field->handle] = array_map($func, $block[$field->handle]->all());
                    break;
                case "craft\\fields\\Users":
                    $func = function ($x) {
                        return [
                            'id' => $x->id,
                            'username' => $x->username,
                            'fullName' => $x->fullName,
                            'name' => $x->name,
                            'email' => $x->email,
                            'status' => $x->status
                        ];
                    };
                    $data[$field->handle] = array_map($func, $block[$field->handle]->all());
                    break;
                case "craft\\fields\\Checkboxes":
                case "craft\\fields\\Dropdown":
                case "craft\\fields\\RadioButtons":
                case "craft\\fields\\MultiSelect":
                    $data[$field->handle] = $block[$field->handle]->getOptions();
                    break;
                case "craft\\fields\\Color":
                    $data[$field->handle] = [
                        "hex" => $block[$field->handle]->getHex(),
                        "rgb" => $block[$field->handle]->getRgb(),
                        "luma" => $block[$field->handle]->getLuma(),
                    ];
                    break;
                default:
                    $data[$field->handle] = $block[$field->handle];
                    break;
            }
        }
        return $data;
    }
}
