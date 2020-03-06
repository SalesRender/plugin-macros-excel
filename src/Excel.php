<?php
/**
 * Created for plugin-core
 * Date: 02.03.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Macros\Excel;


use Adbar\Dot;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Leadvertex\Plugin\Components\ApiClient\ApiClient;
use Leadvertex\Plugin\Components\ApiClient\ApiFilterSortPaginate;
use Leadvertex\Plugin\Components\Developer\Developer;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\Process\Process;
use Leadvertex\Plugin\Components\Purpose\PluginClass;
use Leadvertex\Plugin\Components\Purpose\PluginEntity;
use Leadvertex\Plugin\Components\Purpose\PluginPurpose;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Macros\Components\AutocompleteInterface;
use Leadvertex\Plugin\Core\Macros\Helpers\PathHelper;
use Leadvertex\Plugin\Core\Macros\Models\Session;
use Leadvertex\Plugin\Core\Macros\PluginInterface;
use Leadvertex\Plugin\Instance\Macros\Excel\Components\Columns;
use Leadvertex\Plugin\Instance\Macros\Excel\Components\FieldParser;
use Leadvertex\Plugin\Instance\Macros\Excel\Components\OrdersFetcherIterator;
use Leadvertex\Plugin\Instance\Macros\Excel\Forms\OptionsForm;
use Leadvertex\Plugin\Instance\Macros\Excel\Forms\SettingsForm;
use XAKEPEHOK\Path\Path;

class Excel implements PluginInterface
{

    /** @var ApiClient */
    private $apiClient;

    /** @var SettingsForm */
    private $settings;

    /** @var Form */
    private $run_1;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    /**
     * @inheritDoc
     */
    public static function getLanguages(): array
    {
        return [
            'ru_RU'
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getDefaultLanguage(): string
    {
        return 'ru_RU';
    }

    /**
     * @inheritDoc
     */
    public static function getName(): string
    {
        return Translator::get('info', 'Excel');
    }

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return Translator::get('info', 'Позволяет осуществлять выгрузку заказов в Excel');
    }

    /**
     * @inheritDoc
     */
    public static function getPurpose(): PluginPurpose
    {
        return new PluginPurpose(
            new PluginClass(PluginClass::CLASS_EXPORTER),
            new PluginEntity(PluginEntity::ENTITY_ORDER)
        );
    }

    /**
     * @inheritDoc
     */
    public static function getDeveloper(): Developer
    {
        return new Developer(
            'LeadVertex',
            'support@leadvertex.com',
            'https://leadvertex.com'
        );
    }

    /**
     * @inheritDoc
     */
    public function getSettingsForm(): Form
    {
        if (is_null($this->settings)) {
            $this->settings = new SettingsForm();
        }

        return $this->settings;
    }

    /**
     * @inheritDoc
     */
    public function getRunForm_1(?ApiFilterSortPaginate $fsp): ?Form
    {
        if (is_null($this->run_1)) {
            $this->run_1 = new OptionsForm();
        }
        return $this->run_1;
    }

    /**
     * @inheritDoc
     */
    public function getRunForm_2(?ApiFilterSortPaginate $fsp): ?Form
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function autocomplete(string $name): ?AutocompleteInterface
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function run(Process $process, ?ApiFilterSortPaginate $fsp)
    {
        $iterator = new OrdersFetcherIterator($process, $this->apiClient, $fsp);

        $session = Session::current();
        $settings = $session->getSettings()->getData();
        $options = $session->getOptions_1();
        $fields = $settings->get('main.fields');

        $format = current($options->get('options.format'));
        $ext = '.' . $format;
        $filePath = PathHelper::getPublicOutput()->down($session->getId() . $ext);
        $fileUri = (new Path($_ENV['LV_PLUGIN_SELF_URI']))->down('output')->down($session->getId() . $ext);

        switch ($format) {
            case 'xlsx':
                $writer = WriterEntityFactory::createXLSXWriter();
                break;
            case 'ods':
                $writer = WriterEntityFactory::createODSWriter();
                break;
            case 'csv':
                $writer = WriterEntityFactory::createCSVWriter();
                break;
        }

        $writer->openToFile((string) $filePath);

        if ($settings->get('main.headers')) {
            $headers = [];
            $columns = (new Columns())->getList();
            foreach ($fields as $field) {
                $headers[] = $columns[$field]['title'];
            }
            $writer->addRow(
                WriterEntityFactory::createRowFromArray($headers)
            );
        }

        $iterator->iterator(
            Columns::getQueryColumns($fields),
            function (array $item, Process $process) use ($fields, $writer) {
                $dot = new Dot($item);
                $row = [];
                foreach ($fields as $field) {
                    if (FieldParser::hasFilter($field)) {
                        $field = new FieldParser($field);
                        $array = $dot->get($field->getLeftPart());
                        foreach ($array as $value) {
                            if (!is_array($value)) {
                                continue;
                            }
                            $part = new Dot($value);
                            if ($part->get($field->getFilterProperty()) == $field->getFilterValue()) {
                                $row[] = $part->get($field->getRightPart());
                                break;
                            }
                            $row[] = '';
                        }
                    } else {
                        $row[] = $dot->get($field);
                    }
                }

                $writer->addRow(
                    WriterEntityFactory::createRowFromArray($row)
                );

                $process->handle();
            }
        );

        $writer->close();
        $process->finish((string) $fileUri);
        $process->save();
    }
}