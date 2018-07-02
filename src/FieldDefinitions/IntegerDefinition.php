<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 16:53
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Exports\FieldDefinitions;


class IntegerDefinition extends FieldDefinition
{

    public function __construct(array $names, array $descriptions, $default, $required)
    {
        $default = (int) $default;
        parent::__construct($names, $descriptions, $default, $required);
    }

    /**
     * @return string
     */
    public function definition()
    {
        return 'integer';
    }

    /**
     * @param int $value
     * @return bool
     */
    public function validateValue($value)
    {
        $strValue = (string) (int) $value;
        return $this->required === false || strlen($strValue) > 0;
    }
}