<?php
/**
 * Created for plugin-exporter-excel
 * Date: 05.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace SalesRender\Plugin\Instance\Excel;


use Adbar\Dot;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use DateTime;
use Exception;
use SalesRender\Plugin\Components\ApiClient\ApiClient;
use SalesRender\Plugin\Components\Batch\Batch;
use SalesRender\Plugin\Components\Batch\BatchHandlerInterface;
use SalesRender\Plugin\Components\Batch\Process\Error;
use SalesRender\Plugin\Components\Batch\Process\Process;
use SalesRender\Plugin\Components\Settings\Settings;
use SalesRender\Plugin\Components\Translations\Translator;
use SalesRender\Plugin\Core\Helpers\PathHelper;
use SalesRender\Plugin\Instance\Excel\Components\Columns;
use SalesRender\Plugin\Instance\Excel\Components\FieldParser;
use SalesRender\Plugin\Instance\Excel\Components\OrdersFetcherIterator;
use SalesRender\Plugin\Instance\Excel\Forms\SettingsForm;
use Throwable;
use XAKEPEHOK\Path\Path;
use function Sentry\captureException;

class ExcelHandler implements BatchHandlerInterface
{

    private ApiClient $client;

    public function __invoke(Process $process, Batch $batch)
    {
        $settings = Settings::find()->getData();
        $fields = $settings->get('main.fields', SettingsForm::FIELDS_DEFAULT);

        $token = $batch->getToken();
        $this->client = new ApiClient(
            "{$token->getBackendUri()}companies/{$token->getPluginReference()->getCompanyId()}/CRM",
            (string)$token->getOutputToken()
        );
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

        $writer->openToFile((string)$filePath);

        if ($settings->get('main.headers', SettingsForm::SHOW_HEADERS_DEFAULT)) {
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
                            case 'shipping.createdAt':
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
                            case 'cart.items.price':
                            case 'cart.items.total':
                            case 'cart.items.purchasePrice':
                                $pricing = $this->getRowFromCartItems($order->get('cart'), $field);
                                $pricing = array_map(function ($value) {
                                    return $value / 100;
                                }, $pricing);
                                $row[] = implode(', ', $pricing);
                                break;
                            case 'cart.promotions.price':
                            case 'cart.promotions.total':
                                $pricing = $this->getRowFromCartPromotions($order->get('cart'), $field);
                                $pricing = array_map(function ($value) {
                                    return $value / 100;
                                }, $pricing);
                                $row[] = implode(', ', $pricing);
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
                                $row[] =  implode(', ', $this->getRowFromCartItems($order->get('cart'), $field));
                                break;
                            case 'shipping.attachments':
                                $row[] =  implode(
                                    ', ',
                                    array_map(
                                        function ($attachment) {return "{$attachment['name']}: {$attachment['uri']}";},
                                        $order->get($field)
                                    )
                                );
                                break;
                            case 'cart.promotions.promotion.id':
                            case 'cart.promotions.promotion.name':
                            case 'cart.promotions.promotion.dimensions.length':
                            case 'cart.promotions.promotion.dimensions.width':
                            case 'cart.promotions.promotion.dimensions.height':
                            case 'cart.promotions.quantity':
                                $row[] = implode(', ', $this->getRowFromCartPromotions($order->get('cart'), $field));
                                break;
                            case 'logistic.status.logisticOffice.phones':
                                $row[] = implode(', ', $order->get($field));
                                break;
                            case 'cart.cartInString':
                                $row[] = implode(";\r\n", $this->getCartInOneString($order->get('cart'), $this->getCompanyCurrency()));
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
            } catch (Throwable $exception) {
                captureException($exception);
                $process->addError(new Error(
                    Translator::get('process', 'Ошибка обработки данных'),
                    $id
                ));
            } finally {
                $process->save();
            }
        }

        $writer->close();
        $process->finish((string)$fileUri);
        $process->save();
    }

    private function getRowFromCartItems(array $cart, string $path): array
    {
        $row = [];
        $cart = new Dot($cart);
        if ($cart->has('items')) {
            $array = $cart->get('items');
            foreach ($array as $arrayItem) {
                $arrayItem = new Dot($arrayItem);
                $row[] = $arrayItem->get(str_replace('cart.items.', '', $path), '');
            }
        }

        return $row;
    }
    private function getRowFromCartItemsAndPromotionItems(array $cart, $path): array
    {
        $row = [];
        $cart = new Dot($cart);


        if ($cart->has('items')) {
            $array = $cart->get('items');
            foreach ($array as $arrayItem) {
                $arrayItem = new Dot($arrayItem);
                $row[] = $arrayItem->get(str_replace('cart.items.', '', $path), '');
            }
        }



        if ($cart->has('promotions')) {
            $quantity = $cart->get('promotions.0.quantity');


            $items = $cart->get('promotions.0.items');
            $promotionItems = [];
            foreach ($items as $item) {
                $item = new Dot($item);
                $id = $item->get('sku.item.id');
                $name = $item->get('sku.item.name');


                if (!array_key_exists($id, $promotionItems)) {
                    $promotionItems[$id]['name'] = $name;

                    $promotionItems[$id]['value'] = 1;
                } else {
                    $promotionItems[$id]['value'] += 1;
                }
            }

            foreach ($promotionItems as $item) {
                if ($path === 'cart.items.quantity') {
                    $row[] = $item['value'] * $quantity;
                } elseif ($path === 'cart.items.sku.item.name') {
                    $row[] = $item['name'];
                }
            }
        }

        return $row;
    }

    private function getRowFromCartPromotions(array $cart, string $path): array
    {
        $row = [];
        $cart = new Dot($cart);
        if ($cart->has('promotions')) {
            $array = $cart->get('promotions');
            foreach ($array as $arrayItem) {
                $arrayItem = new Dot($arrayItem);
                $row[] = $arrayItem->get(str_replace('cart.promotions.', '', $path), '');
            }
        }

        return $row;
    }

    private function getCartInOneString(array $cart, string $currencyName): array
    {
        $row = [];
        $cart = new Dot($cart);

        foreach ($cart->get('items') as $item) {
            $item = new Dot($item);
            $row[] = "{$item->get('sku.item.name')}/{$item->get('sku.variation.property')}, " .
                "{$item->get('quantity')} {$item->get('sku.item.units')}, " .
                (int)($item->get('purchasePrice', 0) / 100) . " {$currencyName}, " .
                (int)($item->get('total', 0) / 100) . " {$currencyName}";
        }

        foreach ($cart->get('promotions') as $promotion) {
            $promotion = new Dot($promotion);
            $row[] = "{$promotion->get('promotion.name')}, {$promotion->get('quantity')} " .
                Translator::get('process', 'шт.') . ", " .
                (int)($promotion->get('total', 0) / 100) . " {$currencyName}";
        }

        return $row;
    }

    private function getCompanyCurrency(): string
    {
        $query = <<<QUERY
query {
  company {
    pricing {
      pricing {
        currency
      }
    }
  }
}
QUERY;

        $response = $this->client->query($query, [])->getData();

        $response = new Dot($response);

        return $response->get('company.pricing.pricing.currency');
    }
}