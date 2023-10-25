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
                    $rowValue = '';
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
                                $rowValue = $part->get($field->getRightPart());
                                break;
                            }
                        }
                        $row[] = $rowValue;
                    } else {
                        switch ($field) {
                            case 'createdAt':
                            case 'updatedAt':
                            case 'statusChangedAt':
                            case 'logistic.status.assignmentAt':
                                if ($order->get($field) === null) {
                                    $row[] = '';
                                    break;
                                }
                                $date = new DateTime($order->get($field));
                                $row[] = $date->format('Y-m-d H:i:s (\U\T\C e)');
                                break;
                            case 'vat.price':
                            case 'lead.reward.amount':
                            case 'cart.total':
                                $row[] = (float)$order->get($field) / 100;
                                break;
                            case 'cart.items.sku.item.id':
                            case 'cart.items.sku.item.name':
                            case 'cart.items.sku.item.description':
                            case 'cart.items.sku.item.weight':
                            case 'cart.items.sku.item.dimensions.length':
                            case 'cart.items.sku.item.dimensions.width':
                            case 'cart.items.sku.item.dimensions.height':
                            case 'cart.items.sku.variation.number':
                            case 'cart.items.sku.variation.property':
                            case 'cart.items.quantity':
                            case 'cart.promotions.promotion.id':
                            case 'cart.promotions.promotion.name':
                            case 'cart.promotions.promotion.dimensions.length':
                            case 'cart.promotions.promotion.dimensions.width':
                            case 'cart.promotions.promotion.dimensions.height':
                            case 'cart.promotions.quantity':
                            case 'cart.items.price':
                            case 'cart.items.total':
                            case 'cart.promotions.price':
                            case 'cart.promotions.total':
                                $row[] = $this->getRowFromCart($order->get('cart'), $field);
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

    private function getRowFromCart(array $cart, string $path): string
    {
        $row = [];
        $cart = new Dot($cart);
        if (str_contains($path, 'cart.items.') && $cart->has('items')) {
            $array = $cart->get('items');
            foreach ($array as $arrayItem) {
                $arrayItem = new Dot($arrayItem);
                $row[] = $arrayItem->get(str_replace('cart.items.', '', $path), '');
            }
        } else if (str_contains($path, 'cart.promotions.') && $cart->has('promotions')) {
            $array = $cart->get('promotions');
            foreach ($array as $arrayItem) {
                $arrayItem = new Dot($arrayItem);
                $row[] = $arrayItem->get(str_replace('cart.promotions.', '', $path), '');
            }
        } else {
            return json_encode($cart->all(), JSON_UNESCAPED_UNICODE);
        }

        switch ($path) {
            case 'cart.items.price':
            case 'cart.items.total':
            case 'cart.promotions.price':
            case 'cart.promotions.total':
                $row = array_map(function ($value) {
                    return $value / 100;
                }, $row);
                break;
            case 'cart.items.sku.item.weight':
            case 'cart.items.sku.item.dimensions.length':
            case 'cart.items.sku.item.dimensions.width':
            case 'cart.items.sku.item.dimensions.height':
            case 'cart.promotions.promotion.dimensions.length':
            case 'cart.promotions.promotion.dimensions.width':
            case 'cart.promotions.promotion.dimensions.height':
                $row = array_map(function ($value) {
                    return ($value === '') ? 0 : $value;
                }, $row);
        }

        return implode(', ', $row);
    }
}