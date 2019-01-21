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
use craft\base\Element;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\db\MatrixBlockQuery;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\fields\Assets;
use craft\fields\Categories;
use craft\fields\Checkboxes;
use craft\fields\Color;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Entries;
use craft\fields\Matrix;
use craft\fields\MultiSelect;
use craft\fields\RadioButtons;
use craft\fields\Tags;
use craft\fields\Users;
use craft\models\FieldLayout;
use craft\redactor\FieldData;

use fork\embeds\Embeds as EmbedsPlugin;

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
     * @param string $embedsCopy
     * @param MatrixBlock[] $embeds
     * @return array
     */
    public function mergeEmbeds(string $embedsCopy, array $embeds): array
    {
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
                'data' => $this->getElementData($embed)
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
     * @param Element $element
     * @param array $transforms
     * @return array
     */
    public function getElementData(Element $element, array $transforms = []): array
    {
        // Handle different element types and set their specific attributes
        switch (get_class($element)) {
            case Asset::class:
                /** @var Asset $element */
                $srcset = [];
                foreach ($transforms as $transformSettings) {
                    $transform = Craft::$app->assetTransforms->getTransformById($transformSettings['transformId']);
                    if ($transform) {
                        $srcset[$transformSettings['srcset']][] = [
                            'src' => $element->getUrl($transform->handle),
                            'suffix' => $transformSettings['suffix']
                        ];
                    }
                }

                $data = [
                    'id' => $element->id,
                    'title' => $element->title,
                    'status' => $element->status,
                    'src' => $element->getUrl(),
                    'srcset' => $srcset,
                    'height' => $element->height,
                    'width' => $element->width,
                ];
                break;

            case Category::class:
                /** @var Category $element */
                $data = [
                    'id' => $element->id,
                    'title' => $element->title,
                    'slug' => $element->slug,
                    'status' => $element->status,
                ];
                break;

            case Entry::class:
                /** @var Entry $element */
                $data = [
                    'id' => $element->id,
                    'title' => $element->title,
                    'slug' => $element->slug,
                    'status' => $element->status,
                    'authorId' => $element->author->id,
                    'postDate' => $element->postDate->getTimestamp(),
                    'section' => $element->section->handle,
                    'dateCreated' => $element->dateCreated->getTimestamp(),
                    'dateUpdated' => $element->dateUpdated->getTimestamp()
                ];
                break;

            default:
                $data = [];
                break;
        }
        if ($element->embeds && $element->embedsCopy) {
            /** @var FieldData $copy */
            $copy = $element->embedsCopy;
            /** @var MatrixBlockQuery $embeds */
            $embeds = $element->embeds;
            $data['embeds'] = $this->mergeEmbeds($copy->getRawContent(), $embeds->all());
        }

        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $element->fieldLayout;
        /** @var \craft\base\Field $field */
        foreach ($fieldLayout->getFields() as $field) {
            // Embeds specific fields are getting special treatment
            if (!in_array($field->handle, ["embeds", "embedsCopy"])) {
                switch (get_class($field)) {
                    case Assets::class:
                        $fieldSettings = EmbedsPlugin::$plugin->settings->getSettingsByFieldId($field->id);
                        $transforms = array_key_exists("transforms", $fieldSettings) && $fieldSettings['transforms'] != "" ? $fieldSettings['transforms'] : [];
                        if ($field->limit && $field->limit == 1) {
                            $data[$field->handle] = $element[$field->handle]->one() ? $this->getElementData($element[$field->handle]->one(), $transforms) : null;
                        } else {
                            $data[$field->handle] = [];
                            foreach ($element[$field->handle]->all() as $asset) {
                                $data[$field->handle][] = $this->getElementData($asset, $transforms);
                            }
                        }
                        break;

                    case Categories::class:
                    case Entries::class:
                        if ($field->limit && $field->limit == 1) {
                            $data[$field->handle] = $element[$field->handle]->one() ? $this->getElementData($element[$field->handle]->one()) : null;
                        } else {
                            $data[$field->handle] = array_map([$this, 'getElementData'], $element[$field->handle]->all());
                        }
                        break;

                    case Matrix::class:
                        $data[$field->handle] = array_map([$this, 'getElementData'], $element[$field->handle]->all());
                        break;

                    case Tags::class:
                        $func = function ($x) {
                            return [
                                'id' => $x->id,
                                'title' => $x->title,
                                'slug' => $x->slug,
                                'status' => $x->status
                            ];
                        };
                        $data[$field->handle] = array_map($func, $element[$field->handle]->all());
                        break;

                    case Users::class:
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
                        $data[$field->handle] = array_map($func, $element[$field->handle]->all());
                        break;

                    case Checkboxes::class:
                    case MultiSelect::class:
                        $data[$field->handle] = array_map(function($item) { return $item->value; }, $element[$field->handle]->getArrayCopy());
                        break;

                    case Dropdown::class:
                    case RadioButtons::class:
                        $data[$field->handle] = $element[$field->handle]->value;
                        break;

                    case Date::class:
                        /** @var \DateTime $date */
                        $date = $element[$field->handle];
                        $data[$field->handle] = $date ? $date->getTimestamp() : false;
                        break;

                    case Color::class:
                        $data[$field->handle] = [
                            "hex" => $element[$field->handle]->getHex(),
                            "rgb" => $element[$field->handle]->getRgb(),
                            "luma" => $element[$field->handle]->getLuma(),
                        ];
                        break;

                    default:
                        $data[$field->handle] = $element[$field->handle];
                        break;
                }
            }
        }
        return $data;
    }
}
