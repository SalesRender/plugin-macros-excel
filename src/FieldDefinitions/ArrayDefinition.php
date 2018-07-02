<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:37
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Exports\FieldDefinitions;


class ArrayDefinition extends FieldDefinition
{

    /**
     * @var array
     */
    protected $enum;

    public function __construct($names, $descriptions, $default, $required, $enum = [])
    {
        parent::__construct($names, $descriptions, $default, $required);
        $this->guardFlatArray($enum);
        $this->enum = array_values($enum);
    }

    /**
     * @return string
     */
    public function definition()
    {
        return 'array';
    }

    /**
     * @param array $value
     * @return bool
     */
    public function validateValue($value)
    {
        return $this->required === false || count($value) > 0;
    }

    /**
     * @return array
     */
    public function getEnum()
    {
        return $this->enum;
    }

    public function toArray($language)
    {
        $array = parent::toArray($language);
        $array['enum'] = $this->getEnum();
        return $array;
    }

    private function guardFlatArray($array)
    {
        if (count($array) !== count($array, COUNT_RECURSIVE)) {
            throw new \InvalidArgumentException('Array enum should be flat');
        }
    }
}