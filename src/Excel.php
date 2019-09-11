<?php
/**
 * Created for lv-exporter-excel
 * Datetime: 03.07.2018 12:53
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Handler\Excel;


use Adbar\Dot;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Leadvertex\Plugin\Components\ApiClient\ApiClient;
use Leadvertex\Plugin\Components\ApiClient\ApiFilterSortPaginate;
use Leadvertex\Plugin\Components\Developer\Developer;
use Leadvertex\Plugin\Components\Form\FieldDefinitions\EnumDefinition;
use Leadvertex\Plugin\Components\Form\FieldGroup;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\I18n\I18nInterface;
use Leadvertex\Plugin\Components\Process\Components\Error;
use Leadvertex\Plugin\Components\Process\Components\Handled;
use Leadvertex\Plugin\Components\Process\Components\Result\ResultFailed;
use Leadvertex\Plugin\Components\Process\Components\Result\ResultUrl;
use Leadvertex\Plugin\Components\Process\Exceptions\NotInitializedException;
use Leadvertex\Plugin\Components\Process\Process;
use Leadvertex\Plugin\Components\Purpose\PluginClass;
use Leadvertex\Plugin\Components\Purpose\PluginEntity;
use Leadvertex\Plugin\Components\Purpose\PluginPurpose;
use Leadvertex\Plugin\Handler\Excel\Components\Lang;
use Leadvertex\Plugin\Handler\Excel\Components\OrdersFetcherIterator;
use Leadvertex\Plugin\Handler\Excel\Components\ExcelSettingsForm;
use Leadvertex\Plugin\Handler\PluginInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webmozart\PathUtil\Path;
use XAKEPEHOK\EnumHelper\Exception\OutOfEnumException;

class Excel implements PluginInterface
{

    /** @var Form */
    private $settingsForm;

    /** @var Form */
    private $optionsForm;

    /** @var ApiClient */
    private $apiClient;

    /** @var string */
    private $runtimeDir;

    /** @var string */
    private $outputDir;

    /** @var string */
    private $outputUrl;

    public function __construct(ApiClient $apiClient, string $runtimeDir, string $outputDir, string $outputUrl)
    {
        $this->apiClient = $apiClient;
        $this->runtimeDir = $runtimeDir;
        $this->outputDir = $outputDir;
        $this->outputUrl = $outputUrl;
    }

    /**
     * @see \Leadvertex\Plugin\Components\I18n\I18nInterface::getLanguages
     * @return array
     */
    public static function getLanguages(): array
    {
        return Lang::getLanguages();
    }

    public static function getDefaultLanguage(): string
    {
        return I18nInterface::en_US;
    }

    /**
     * Should return human-friendly name of this exporter
     * @return I18nInterface
     */
    public static function getName(): I18nInterface
    {
        return new Lang(
            "Excel",
            "Excel"
        );
    }

    /**
     * Should return human-friendly description of this exporter
     * @return I18nInterface
     */
    public static function getDescription(): I18nInterface
    {
        return new Lang(
            "Export orders to excel file",
            "Выгружает заказы в excel файл"
        );
    }

    public function getDeveloper(): Developer
    {
        return new Developer('LeadVertex', 'support@leadvertex.com', 'plugins.leadvertex.com');
    }

    /**
     * @return PluginPurpose of entities, that can be handled by plugin
     * @throws OutOfEnumException
     */
    public function getPurpose(): PluginPurpose
    {
        return new PluginPurpose(
            new PluginClass(PluginClass::CLASS_EXPORTER),
            new PluginEntity(PluginEntity::ENTITY_ORDER)
        );
    }

    public function hasSettingsForm(): bool
    {
        return true;
    }

    /**
     * Should return settings form for plugin configs
     * @return Form
     * @throws Exception
     */
    public function getSettingsForm(): Form
    {
        if (!$this->settingsForm) {
            $this->settingsForm = new ExcelSettingsForm($this->apiClient);
        }

        return $this->settingsForm;
    }

    public function hasOptionsForm(): bool
    {
        return true;
    }

    /**
     * Should return form for plugin options (before-handle form)
     * @return Form
     * @throws Exception
     */
    public function getOptionsForm(): Form
    {
        if (!$this->optionsForm) {
            $this->optionsForm = new Form(
                new Lang('Export options', 'Опции выгрузки'),
                new Lang('One-time export options', 'Единоразовые опции выгрузки'),
                [
                    'main' => new FieldGroup(
                        new Lang('Excel', 'Excel'),
                        [
                            'format' => new EnumDefinition(
                                new Lang(
                                    "File format",
                                    "Формат файла"
                                ),
                                new Lang(
                                    "csv - simple plain-text format, xls - old excel 2003 format, xlsx - new excel format",
                                    "csv - простой текстовый формат, xls - формат excel 2003, xlsx - новый формат excel"
                                ),
                                [
                                    'csv' => new Lang(
                                        "*.csv - simple plain text format",
                                        "*.csv - простой текстовый формат"
                                    ),
                                    'xls' => new Lang(
                                        "*.xls - Excel 2003",
                                        "*.xls - Формат Excel 2003"
                                    ),
                                    'xlsx' => new Lang(
                                        "*.xls - Excel 2007 and newer",
                                        "*.xls - Формат Excel 2007 и новее"
                                    ),
                                ],
                                $this->getSettingsForm()->getData()->get('main.format'),
                                true
                            ),
                        ]
                    )
                ]
            );
        }
        return $this->optionsForm;
    }

    /**
     * @param Process $process
     * @param ApiFilterSortPaginate|null $fsp
     * @throws GuzzleException
     * @throws NotInitializedException
     */
    public function handle(Process $process, ?ApiFilterSortPaginate $fsp)
    {
        $settingsData = $this->settingsForm->getData();
        $optionsData = $this->optionsForm->getData();

        $format = $optionsData->get('main.format');
        $fileName = "{$process->getId()}.{$format}";
        $filePath = Path::canonicalize("{$this->outputDir}/{$fileName}");
        $fileUrl = $this->outputUrl . "/{$fileName}";

        $iterator = new OrdersFetcherIterator($process, $this->apiClient, $fsp);

        try {
            switch ($format) {
                case 'csv':
                    $csv = fopen($filePath, 'w');

                    //First row is the column captions
                    if ($settingsData->get('main.headers')) {
                        fputcsv($csv, $settingsData->get('main.fields'));
                    }

                    $iterator->iterator($this->getOrderBodyFields(), function (array $data) use ($csv) {
                        $row = $this->getOrderDataAsFlatArray($data);
                        fputcsv($csv, $row);
                        return new Handled(1);
                    });

                    fclose($csv);
                    break;
                case 'xls':
                case 'xlsx':
                    $spreadsheet = new Spreadsheet();

                    $sheet = $spreadsheet->getActiveSheet();
                    $currentRow = 1;

                    //First row is the column captions
                    if ($settingsData->get('main.headers')) {
                        $sheet->fromArray($settingsData->get('main.fields'), null, 'A' . $currentRow++);
                    }

                    $iterator->iterator($this->getOrderBodyFields(), function (array $data) use ($sheet, &$currentRow) {
                        $row = $this->getOrderDataAsFlatArray($data);
                        $sheet->fromArray($row, null, 'A' . $currentRow++);
                        return new Handled(1);
                    });

                    $writer = $format === 'xls' ? new Xlsx($spreadsheet) : new Xls($spreadsheet);
                    $writer->save($filePath);
                    break;
            }

            $process->resultWebhook(new ResultUrl($fileUrl));
        } catch (Exception $exception) {
            $this->processFatalError($process);
            throw $exception;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getOrderBodyFields(): array
    {
        $fields = [];
        foreach ($this->getSettingsForm()->getData()->get('main.fields') as $field) {
            $items = array_reverse(explode('.', $field));
            $tree = [];
            foreach ($items as $item) {
                if (empty($tree)) {
                    $tree[] = $item;
                } else {
                    $tree = [$item => $tree];
                }
            }
            $fields = array_merge_recursive($fields, $tree);
        }

        $fields[] = 'id';
        return ['orders' => $fields];
    }

    /**
     * @param array $data
     * @return array
     * @throws Exception
     */
    private function getOrderDataAsFlatArray(array $data): array
    {
        $dot = new Dot($data);
        $result = [];
        foreach ($this->getSettingsForm()->getData()->get('main.fields') as $field) {
            $result[] = $dot->get($field, '');
        }
        return  $result;
    }

    /**
     * @param Process $process
     * @throws GuzzleException
     * @throws NotInitializedException
     */
    private function processFatalError(Process $process)
    {
        $error = new Error(new Lang(
            'An unknown error occurred during exporting data. Exporting are stopped',
            'Во время выгрузки произошла неизвестная ошибка. Выгрузка прекращена'
        ));

        $process->errorWebhook([$error]);
        $process->resultWebhook(new ResultFailed());
    }
}