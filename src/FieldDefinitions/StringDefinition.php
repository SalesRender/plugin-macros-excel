<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:37
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Exports\FieldDefinitions;


class StringDefinition extends FieldDefinition
{

    public function __construct($names, $descriptions, $default, $required)
    {
        $default = (string) $default;
        parent::__construct($names, $descriptions, $default, $required);
    }

    /**
     * @return string
     */
    public function definition()
    {
        return 'string';
    }

    /**
     * @param string $value
     * @return bool
     */
    public function validateValue($value)
    {
        $value = trim($value);
        return $this->required === false || strlen($value) > 0;
    }
}