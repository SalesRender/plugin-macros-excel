<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 16:52
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\App\FieldDefinitions;


class CheckboxDefinition extends FieldDefinition
{

    /**
     * @return string
     */
    public function definition(): string
    {
        return 'checkbox';
    }

    /**
     * @param bool $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        $value = (bool) $value;
        return $this->required === false || $value === true;
    }
}