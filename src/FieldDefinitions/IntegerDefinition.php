<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 16:53
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\App\FieldDefinitions;


class IntegerDefinition extends FieldDefinition
{

    public function __construct(array $names, array $descriptions, $default, bool $required)
    {
        $default = (int) $default;
        parent::__construct($names, $descriptions, $default, $required);
    }

    /**
     * @return string
     */
    public function definition(): string
    {
        return 'integer';
    }

    /**
     * @param int $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        $strValue = (string) (int) $value;
        return $this->required === false || strlen($strValue) > 0;
    }
}