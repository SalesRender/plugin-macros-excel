<?php
/**
 * Created for plugin-exporter-excel
 * Date: 05.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Excel;


use Adbar\Dot;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use DateTime;
use Exception;
use Leadvertex\Plugin\Components\Batch\Batch;
use Leadvertex\Plugin\Components\Batch\BatchHandlerInterface;
use Leadvertex\Plugin\Components\Batch\Process\Error;
use Leadvertex\Plugin\Components\Batch\Process\Process;
use Leadvertex\Plugin\Components\Settings\Settings;
use Leadvertex\Plugin\Components\Translations\Translator;
use Leadvertex\Plugin\Core\Helpers\PathHelper;
use Leadvertex\Plugin\Instance\Excel\Components\Columns;
use Leadvertex\Plugin\Instance\Excel\Components\FieldParser;
use Leadvertex\Plugin\Instance\Excel\Components\OrdersFetcherIterator;
use XAKEPEHOK\Path\Path;

class ExcelHandler implements BatchHandlerInterface
{


    public function __invoke(Process $process, Batch $batch)
    {
        $settings = Settings::find()->getData();
        $fields = $settings->get('main.fields', []);

        $format = current($batch->getOptions(1)->get('options.format'));
        $ext = '.' . $format;
        $filePath = PathHelper::getPublicOutput()->down($batch->getId() . $ext);
        $fileUri = (new Path($_ENV['LV_PLUGIN_SELF_URI']))->down('output')->down($batch->getId() . $ext);

        switch ($format) {
            case 'xlsx':
                $writer = WriterEntityFactory::createXLSXWriter();
                break;
            case 'ods':
                $writer = WriterEntityFactory::createODSWriter();
                break;
            default:
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

        $ordersIterator = new OrdersFetcherIterator(
            Columns::getQueryColumns($fields),
            $batch->getApiClient(),
            $batch->getFsp()
        );

        $process->initialize(count($ordersIterator));

        foreach ($ordersIterator as $id => $order) {
            try {
                $order = new Dot($order);
                $row = [];
                foreach ($fields as $field) {
                    if (FieldParser::hasFilter($field)) {
                        $field = new FieldParser($field);
                        $array = $order->get($field->getLeftPart());
                        if (empty($array)) {
                            $row[] = '';
                            continue;
                        }
                        foreach ($array as $value) {
                            if (!is_array($value)) {
                                $row[] = '';
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
                        switch ($field) {
                            case 'createdAt':
                            case 'updatedAt':
                            case 'statusChangedAt':
                            case 'logistic.status.assignmentAt':
                                $date = new DateTime($order->get($field));
                                $row[] = $date->format('Y-m-d H:i:s (\U\T\C e)');
                                break;
                            case 'vat.price':
                            case 'lead.reward.amount':
                            case 'cart.total':
                                $row[] = (float)$order->get($field) / 100;
                                break;
                            case 'logistic.status.logisticOffice.phones':
                                $row[] = implode(', ', $order->get($field));
                                break;
                            default:
                                $row[] = $order->get($field);
                        }
                    }
                }

                $writer->addRow(
                    WriterEntityFactory::createRowFromArray($row)
                );

                $process->handle();
            } catch (Exception $exception) {
                $process->addError(new Error(
                    Translator::get('process', 'Ошибка обработки данных'),
                    $id
                ));
            }
            $process->save();
        }

        $writer->close();
        $process->finish((string) $fileUri);
        $process->save();
    }
}