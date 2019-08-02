<?php
/**
 * Created for lv-exporter-excel
 * Datetime: 29.07.2019 16:35
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Exporter\Handler\Components;


use Leadvertex\Plugin\Components\I18n\I18nInterface;

class Lang implements I18nInterface
{

    /** @var string[] */
    private $translations;

    public function __construct(string $en, string $ru)
    {
        $this->translations = [
            'en' => [
                'lang' => 'en',
                'text' => $en,
            ],
            'ru' => [
                'lang' => 'ru',
                'text' => $ru,
            ],
        ];
    }

    /**
     * Every language code should be alpha-2 code
     * @see https://en.wikipedia.org/wiki/ISO_639-1
     *
     * Example:
     * [
     *      'en' => [
     *          'lang' => 'en',
     *          'text' => 'Message',
     *      ],
     *      'ru' => [
     *          'lang' => 'ru',
     *          'text' => 'Сообщение',
     *      ],
     *      'es' => [
     *          'lang' => 'es',
     *          'text' => 'El mensaje',
     *      ],
     * ]
     *     *
     * @return array
     */
    public function get(): array
    {
        return $this->translations;
    }

    /**
     * Method should return array of used languages by alpha-2 code
     * @see https://en.wikipedia.org/wiki/ISO_639-1
     * @return array, for example ['en', 'ru', 'es', ...]
     */
    public static function getLanguages(): array
    {
        return ['en', 'ru'];
    }
}