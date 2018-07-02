<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:33
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Exports\FieldDefinitions;


abstract class FieldDefinition
{

    protected $names = [];
    protected $descriptions = [];
    protected $default;
    protected $required;

    /**
     * ConfigDefinition constructor.
     * @param string[] $names. Property name in different languages. If array, first value are default if language
     * undefined. For example array('en' => 'Organization name', 'ru' => 'Название организации') - default en.
     * @param string[] $descriptions. Property description in different languages. Same behavior, as $names
     * @param string|int|float|bool|array|null $default value
     * @param bool $required is this field required
     */
    public function __construct($names, $descriptions, $default, $required)
    {
        $this->names = $names;
        $this->descriptions = $descriptions;
        $this->default = $default;
        $this->required = $required;
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
     * Value, witch will be used as default
     * @return string|int|float|bool|array|null
     */
    public function getDefaultValue()
    {
        return $this->default;
    }

    /**
     * Does this field will be required
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return string
     */
    abstract public function definition();

    /**
     * @param $value
     * @return bool
     */
    abstract public function validateValue($value);

    public function toArray($language)
    {
        return [
            'definition' => $this->definition(),
            'name' => $this->getName($language),
            'description' => $this->getDescription($language),
            'default' => $this->default,
            'required' => (bool) $this->required,
        ];
    }

    protected function getTranslation($array, $language)
    {
        if (isset($array[$language])) {
            return $array[$language];
        }
        return reset($array);
    }

}