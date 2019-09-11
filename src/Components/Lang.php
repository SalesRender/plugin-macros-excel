<?php
/**
 * Created for lv-exporter-excel
 * Datetime: 29.07.2019 16:35
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Handler\Excel\Components;


use Leadvertex\Plugin\Components\I18n\I18nInterface;

class Lang implements I18nInterface
{

    /** @var string[] */
    private $translations;

    public function __construct(string $en_US, string $ru_RU)
    {
        $this->translations = [
            I18nInterface::en_US => [
                'lang' => I18nInterface::en_US,
                'text' => $en_US,
            ],
            I18nInterface::ru_RU => [
                'lang' => I18nInterface::ru_RU,
                'text' => $ru_RU,
            ],
        ];
    }

    public function get(): array
    {
        return $this->translations;
    }

    public static function getLanguages(): array
    {
        return [I18nInterface::en_US, I18nInterface::ru_RU];
    }
}