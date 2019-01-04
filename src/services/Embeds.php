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
use craft\models\FieldLayout;
use craft\redactor\FieldData;

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
     * @return array
     */
    public function getElementData(Element $element): array
    {
        // Handle different element types and set their specific attributes
        switch (get_class($element)) {
            case "craft\\elements\\Asset":
                /** @var Asset $element */
                $data = [
                    'id' => $element->id,
                    'title' => $element->title,
                    'status' => $element->status,
                    'src' => $element->getUrl(),
                    'height' => $element->height,
                    'width' => $element->width,
                    'srcset' => []
                ];
                break;

            case "craft\\elements\\Category":
                /** @var Category $element */
                $data = [
                    'id' => $element->id,
                    'title' => $element->title,
                    'slug' => $element->slug,
                    'status' => $element->status,
                ];
                break;

            case "craft\\elements\\Entry":
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
                    case "craft\\fields\\Assets":
                    case "craft\\fields\\Categories":
                    case "craft\\fields\\Entries":
                    case "craft\\fields\\Matrix":
                        $data[$field->handle] = array_map([$this, 'getElementData'], $element[$field->handle]->all());
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
                        $data[$field->handle] = array_map($func, $element[$field->handle]->all());
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
                        $data[$field->handle] = array_map($func, $element[$field->handle]->all());
                        break;
                    case "craft\\fields\\Checkboxes":
                    case "craft\\fields\\Dropdown":
                    case "craft\\fields\\RadioButtons":
                    case "craft\\fields\\MultiSelect":
                        $data[$field->handle] = $element[$field->handle]->getOptions();
                        break;
                    case "craft\\fields\\Color":
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
