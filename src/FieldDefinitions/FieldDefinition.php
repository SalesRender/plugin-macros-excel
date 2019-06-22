<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:33
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\App\FieldDefinitions;


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
    public function __construct(array $names, array $descriptions, $default, bool $required)
    {
        $this->names = $names;
        $this->descriptions = $descriptions;
        $this->default = $default;
        $this->required = $required;
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
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    abstract public function definition(): string;

    /**
     * @param $value
     * @return bool
     */
    abstract public function validateValue($value): bool;

    public function toArray(): array
    {
        return [
            'definition' => $this->definition(),
            'name' => $this->names,
            'description' => $this->descriptions,
            'default' => $this->default,
            'required' => (bool) $this->required,
        ];
    }

    protected function getTranslation(array $array, string $language): string
    {
        if (isset($array[$language])) {
            return $array[$language];
        }
        return reset($array);
    }

}