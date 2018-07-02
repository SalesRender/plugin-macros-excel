<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 16:59
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Exports;


use Leadvertex\External\Exports\FieldDefinitions\FieldDefinition;
use TypeError;

abstract class ExportDefinition
{

    protected $id;
    protected $names = [];
    protected $descriptions = [];
    protected $fields = [];

    /**
     * ConfigDefinition constructor.
     * @param string[] $names . Export name in different languages. If array, first value are default if language
     * undefined. For example array('en' => 'Organization name', 'ru' => 'Название организации') - default en.
     * @param string[] $descriptions . Export description in different languages. Same behavior, as $names
     * @param FieldDefinition[] $fieldDefinitions
     */
    public function __construct($names, $descriptions, $fieldDefinitions)
    {
        $this->names = $names;
        $this->descriptions = $descriptions;

        foreach ($fieldDefinitions as $fieldName => $fieldDefinition) {
            if (!$fieldDefinition instanceof FieldDefinition) {
                throw new TypeError('Every item of $fieldsDefinitions should be instance of ' . FieldDefinition::class);
            }
            $this->fields[$fieldName] = $fieldDefinition;
        }

        $this->id = substr(strrchr(get_class($this), '\\'), 1);
    }

    /**
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return property name in passed language. If passed language was not defined, will return name in default language
     * @param string $language
     * @return string
     */
    public function getName($language)
    {
        return $this->getTranslation($this->names, $language);
    }

    /**
     * Return property description in passed language. If passed language was not defined, will return description in default language
     * @param string $language
     * @return string
     */
    public function getDescription($language)
    {
        return $this->getTranslation($this->descriptions, $language);
    }

    /**
     * @return FieldDefinition[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param string $language
     * @return array
     */
    public function toArray($language)
    {
        $array = [
            'id' => $this->getId(),
            'name' => $this->getName($language),
            'description' => $this->getDescription($language),
            'fields' => [],
        ];

        foreach ($this->getFields() as $fieldName => $fieldDefinition) {
            $array['fields'][$fieldName] = $fieldDefinition->toArray($language);
        }

        return $array;
    }

    protected function getTranslation($array, $language)
    {
        if (isset($array[$language])) {
            return $array[$language];
        }
        return reset($array);
    }

}