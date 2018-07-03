<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 16:54
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\App\FieldDefinitions;


class FloatDefinition extends FieldDefinition
{

    public function __construct(array $names, array $descriptions, $default, bool $required)
    {
        $default = (float) $default;
        parent::__construct($names, $descriptions, $default, $required);
    }

    /**
     * @return string
     */
    public function definition(): string
    {
        return 'float';
    }

    /**
     * @param float $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        $strValue = (string) (float) $value;
        return $this->required === false || strlen($strValue) > 0;
    }
}