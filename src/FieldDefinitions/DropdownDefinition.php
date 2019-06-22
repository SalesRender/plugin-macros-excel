<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 16:07
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Export\App\FieldDefinitions;


class DropdownDefinition extends FieldDefinition
{

    protected $dropdownItems;

    /**
     * ConfigDefinition constructor.
     * @param string[] $names . Property name in different languages. If array, first value are default if language
     * undefined. For example array('en' => 'Organization name', 'ru' => 'Название организации') - default en
     * @param string[] $descriptions . Property description in different languages. Same behavior, as $names
     * @param array $dropdownItems witch represent value => caption dropdown in different languages. First language are default.
     * For example (en will be default): array(
     * '01' => array('en' => 'January', 'ru' => 'Январь'),
     * '02' => array('en' => 'February', 'ru' => 'Февраль'),
     * )
     * @param string|int|float|bool|null $default value
     * @param bool $required is this field required
     */
    public function __construct(array $names, array $descriptions, array $dropdownItems, $default, bool $required)
    {
        $this->dropdownItems = $dropdownItems;
        parent::__construct($names, $descriptions, $default, $required);
    }

    /**
     * @return string
     */
    public function definition(): string
    {
        return 'dropdown';
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['dropdownItems'] = $this->dropdownItems;
        return $array;
    }

    /**
     * @param string|int|float|null $value
     * @return bool
     */
    public function validateValue($value): bool
    {
        return $this->required === false || isset($this->dropdownItems[$value]);
    }
}