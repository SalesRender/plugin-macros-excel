<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 16:07
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Exports\FieldDefinitions;


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
        '01' => array('en' => 'January', 'ru' => 'Январь'),
        '02' => array('en' => 'February', 'ru' => 'Февраль'),
     * )
     * @param string|int|float|bool|null $default value
     * @param bool $required is this field required
     */
    public function __construct($names, $descriptions, $dropdownItems, $default, $required)
    {
        $this->dropdownItems = $dropdownItems;
        parent::__construct($names, $descriptions, $default, $required);
    }

    /**
     * Return dropdown in passed language. If passed language was not defined, will return name in default language.
     * For example: array (
        '01' => January',
        '02' => February',
     * )
     * @param string $language
     * @return string
     */
    public function getDropdownItems($language)
    {
        return array_map(function ($value) use ($language){
            return $this->getTranslation($value, $language);
        }, $this->dropdownItems);
    }

    /**
     * @return string
     */
    public function definition()
    {
        return 'dropdown';
    }

    public function toArray($language)
    {
        $array = parent::toArray($language);
        $array['dropdownItems'] = $this->getDropdownItems($language);
        return $array;
    }

    /**
     * @param string|int|float|null $value
     * @return bool
     */
    public function validateValue($value)
    {
        return $this->required === false || isset($this->dropdownItems[$value]);
    }
}