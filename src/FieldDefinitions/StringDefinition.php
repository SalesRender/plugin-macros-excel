<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 15:37
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\App\FieldDefinitions;


class StringDefinition extends FieldDefinition
{

    public function __construct(array $names, array $descriptions, $default, bool $required)
    {
        $default = (string) $default;
        parent::__construct($names, $descriptions, $default, $required);
    }

    /**
     * @return string
     */
    public function definition(): string
    {
        return 'string';
    }

    /**
     * @param string $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        $value = trim($value);
        return $this->required === false || strlen($value) > 0;
    }
}