<?php
/**
 * Embeds Services
 *
 * @link      fork.de
 * @copyright Copyright (c) 2018 Fork
 */

namespace fork\embeds\services;

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
use craft\redactor\Field;
use craft\redactor\FieldData;

use DateTime;

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
    private function mergeEmbeds(string $embedsCopy, array $embeds): array
    {
        // Handle copy
        $embedsCopy = str_replace("\n", "", $embedsCopy);
        $embedsCopy = preg_replace('/<p><br \/><\/p>/', '', $embedsCopy);
        $copySegments = [];
        $splitted = preg_split('/(<!--pagebreak-->|<hr class=\"redactor_pagebreak\"[^>]*>)/', $embedsCopy);
        for ($i = 0; $i < sizeof($splitted); $i++) {
            $copySegments[] = [
                'type' => "copy",
                'html' => $splitted[$i]
            ];
            /**
             * add an embed placeholder after each element, except for the last one, if it's empty. if the last element
             * in the redactor field is an embed, the last element in $splitted will be an empty string.
             */
            if (!($i == sizeof($splitted)-1 && empty($splitted[$i]))) {
                $copySegments[] = [
                    'type' => "embedPlaceholder"
                ];
            }
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

        $embedCounter = 0;
        $merged = [];
        foreach ($copySegments as $copySegment) {
            if ($copySegment['type'] == "copy" && !empty($copySegment['html'])) {
                $merged[] = $copySegment;
            } else  if ($copySegment['type'] == "embedPlaceholder" && $embedCounter < sizeof($embedBlocks)) {
                $merged[] = $embedBlocks[$embedCounter++];
            }
        }
        return $merged;
    }

    /**
     * Converts a DateTime object to an array of it's attributes
     * @param DateTime $dateTime
     * @param bool $date
     * @param bool $time
     * @return array
     */
    public function convertDateTime(DateTime $dateTime, $date = true, $time = true) : array
    {
        $converted = [
            'c' => date_format($dateTime, 'c'),
        ];
        if ($date) {
            $converted = array_merge(
                $converted,
                [
                    'Y' => date_format($dateTime, 'Y'),
                    'm' => date_format($dateTime, 'm'),
                    'd' => date_format($dateTime, 'd'),
                ]
            );
        }
        if ($time) {
            $converted = array_merge(
                $converted,
                [
                    'H' => date_format($dateTime, 'H'),
                    'i' => date_format($dateTime, 'i'),
                ]
            );
        }
        return $converted;
    }

    /**
     * @param Element $element
     * @return array
     */
    public function getElementData(Element $element, int $nestingLevel = 0): array
    {
        // Handle different element types and set their specific attributes
        switch (get_class($element)) {
            case Asset::class:
                /** @var Asset $element */
                $data = [
                    'id' => $element->id,
                    'title' => $element->title,
                    'status' => $element->status,
                    'src' => $element->getUrl(),
                    'height' => $element->height,
                    'width' => $element->width,
                    'filesize' => $element->size,
                    'mimeType' => $element->mimeType,
                    'dateCreated' => $this->convertDateTime($element->dateCreated)
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
                    'type' => $element->type->handle,
                    'title' => $element->title,
                    'slug' => $element->slug,
                    'status' => $element->status,
                    'authorId' => $element->section->type == "single" ? null : $element->author->id,
                    'postDate' => $this->convertDateTime($element->postDate),
                    'section' => $element->section->handle,
                    'dateCreated' => $this->convertDateTime($element->dateCreated),
                    'dateUpdated' => $this->convertDateTime($element->dateUpdated)
                ];
                break;

            case MatrixBlock::class:
                /** @var MatrixBlock $element */
                $data = [
                    'id' => $element->id,
                    'type' => $element->type->handle,
                ];
                break;

            default:
                $data = [
                    'id' => $element->id
                ];
                break;
        }

        if ($nestingLevel > 10) {
            return $data;
        }

        if ($element->embeds && $element->embedsCopy) {
            /** @var FieldData $copy */
            $copy = $element->embedsCopy;
            /** @var MatrixBlockQuery $embeds */
            $embeds = $element->embeds;
            $data['embeds'] = $this->mergeEmbeds($copy->getParsedContent(), $embeds->all());
        }

        /** @var FieldLayout $fieldLayout */
        $fieldLayout = $element->fieldLayout;
        /** @var \craft\base\Field $field */
        foreach ($fieldLayout->getFields() as $field) {
            // Embeds specific fields are getting special treatment
            if (!in_array($field->handle, ["embeds", "embedsCopy"])) {
                switch (get_class($field)) {
                    case Assets::class:
                        if ($field->limit && $field->limit == 1) {
                            $data[$field->handle] = $element[$field->handle]->one() ? $this->getElementData($element[$field->handle]->one(), ++$nestingLevel) : null;
                        } else {
                            $nestingLevel++;
                            $data[$field->handle] = array_map(function($elem) use ($nestingLevel) {
                                return $this->getElementData($elem, $nestingLevel);
                            }, $element[$field->handle]->all());
                        }
                        break;

                    case Categories::class:
                    case Entries::class:
                        if ($field->limit && $field->limit == 1) {
                            $data[$field->handle] = $element[$field->handle]->one() ? $this->getElementData($element[$field->handle]->one(), ++$nestingLevel) : null;
                        } else {
                            $nestingLevel++;
                            $data[$field->handle] = array_map(function($elem) use ($nestingLevel) {
                                return $this->getElementData($elem, $nestingLevel);
                            }, $element[$field->handle]->all());
                        }
                        break;

                    case Matrix::class:
                        /** @var Matrix $field */
                        if ($field->maxBlocks && $field->maxBlocks == 1) {
                            $data[$field->handle] = $element[$field->handle]->one() ? $this->getElementData($element[$field->handle]->one(), ++$nestingLevel) : null;
                        } else {
                            $nestingLevel++;
                            $data[$field->handle] = array_map(function($elem) use ($nestingLevel) {
                                return $this->getElementData($elem, $nestingLevel);
                            }, $element[$field->handle]->all());
                        }
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
                        /** @var Date $field */
                        /** @var \DateTime $date */
                        $date = $element[$field->handle];
                        $data[$field->handle] = $date ? $this->convertDateTime($date, $field->showDate, $field->showTime) : false;
                        break;

                    case Color::class:
                        $data[$field->handle] = [
                            "hex" => $element[$field->handle]->getHex(),
                            "rgb" => $element[$field->handle]->getRgb(),
                            "luma" => $element[$field->handle]->getLuma(),
                        ];
                        break;

                    case Field::class:
                        /** @var FieldData $copy */
                        $copy = $element[$field->handle];
                        $copy = $copy ? $copy->getParsedContent() : "";
                        $copy = str_replace("\n", "", $copy);
                        $data[$field->handle] = $copy;
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
