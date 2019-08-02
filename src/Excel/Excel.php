<?php
/**
 * Created for lv-exporter-excel
 * Datetime: 03.07.2018 12:53
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Exporter\Handler\Excel;


use Adbar\Dot;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Leadvertex\Plugin\Components\ApiClient\ApiClient;
use Leadvertex\Plugin\Components\Developer\Developer;
use Leadvertex\Plugin\Components\Form\Form;
use Leadvertex\Plugin\Components\I18n\I18nInterface;
use Leadvertex\Plugin\Components\Process\Components\Error;
use Leadvertex\Plugin\Components\Process\Components\Handled;
use Leadvertex\Plugin\Components\Process\Components\Result\ResultFailed;
use Leadvertex\Plugin\Components\Process\Components\Result\ResultUrl;
use Leadvertex\Plugin\Components\Process\Exceptions\NotInitializedException;
use Leadvertex\Plugin\Components\Process\Process;
use Leadvertex\Plugin\Components\Purpose\PluginEntity;
use Leadvertex\Plugin\Exporter\Core\Components\GenerateParams;
use Leadvertex\Plugin\Exporter\Core\ExporterInterface;
use Leadvertex\Plugin\Exporter\Handler\Components\ExporterException;
use Leadvertex\Plugin\Exporter\Handler\Components\Lang;
use Leadvertex\Plugin\Exporter\Handler\Components\OrdersFetcherIterator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webmozart\PathUtil\Path;
use XAKEPEHOK\EnumHelper\Exception\OutOfEnumException;

class Excel implements ExporterInterface
{

    /** @var Form */
    private $form;

    /** @var ApiClient */
    private $apiClient;

    /** @var string */
    private $runtimeDir;

    /** @var string */
    private $publicDir;

    /** @var string */
    private $publicUrl;

    public function __construct(ApiClient $apiClient, string $runtimeDir, string $publicDir, string $publicUrl)
    {
        $this->apiClient = $apiClient;
        $this->runtimeDir = $runtimeDir;
        $this->publicDir = $publicDir;
        $this->publicUrl = $publicUrl;
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
        return 'en';
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
        return new Developer('LeadVertex', 'support@leadvertex.com', 'exports.leadvertex.com');
    }

    /**
     * @return PluginEntity of entities, that can be exported by plugin
     * @throws OutOfEnumException
     */
    public function getEntity(): PluginEntity
    {
        return new PluginEntity(PluginEntity::ENTITY_ORDER);
    }

    /**
     * @return Form
     * @throws Exception
     */
    public function getForm(): Form
    {
        if (!$this->form) {
            $this->form = new ExcelForm($this->apiClient);
        }

        return $this->form;
    }

    /**
     * @param GenerateParams $params
     * @throws ExporterException
     * @throws GuzzleException
     * @throws NotInitializedException
     */
    public function generate(GenerateParams $params)
    {
        if (!$this->getForm()->setData($params->getFormData())) {
            throw new ExporterException('Invalid form data');
        }

        $data = $this->getForm()->getData();

        $format = $data->get('main.format');
        $fileName = "{$params->getProcess()->getId()}.{$format}";
        $filePath = Path::canonicalize("{$this->publicDir}/{$fileName}");
        $fileUrl = $this->publicUrl . "/{$fileName}";

        $process = $params->getProcess();
        $iterator = new OrdersFetcherIterator($process, $this->apiClient, $params->getFsp());

        try {
            switch ($format) {
                case 'csv':
                    $csv = fopen($filePath, 'w');

                    //First row is the column captions
                    if ($data->get('main.headers')) {
                        fputcsv($csv, $data->get('main.fields'));
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
                    $spreadsheet->getProperties()->setCategory($this->getEntity()->get());

                    $sheet = $spreadsheet->getActiveSheet();
                    $currentRow = 1;

                    //First row is the column captions
                    if ($data->get('main.headers')) {
                        $sheet->fromArray($data->get('main.fields'), null, 'A' . $currentRow++);
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
        foreach ($this->getForm()->getData()->get('main.fields') as $field) {
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

    private function getOrderDataAsFlatArray(array $data): array
    {
        $dot = new Dot($data);
        $result = [];
        foreach ($this->getForm()->getData()->get('main.fields') as $field) {
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