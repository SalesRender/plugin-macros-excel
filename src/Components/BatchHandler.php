<?php
/**
 * Created for plugin-exporter-excel
 * Date: 05.11.2020
 * @author Timur Kasumov (XAKEPEHOK)
 */

namespace Leadvertex\Plugin\Instance\Macros\Components;


use Adbar\Dot;
use Box\Spout\Writer\Common\Creator\WriterEntityFactory;
use Leadvertex\Plugin\Components\Batch\Batch;
use Leadvertex\Plugin\Components\Batch\BatchHandlerInterface;
use Leadvertex\Plugin\Components\Process\Process;
use Leadvertex\Plugin\Core\Macros\Helpers\PathHelper;
use Leadvertex\Plugin\Instance\Macros\Plugin;
use XAKEPEHOK\Path\Path;

class BatchHandler implements BatchHandlerInterface
{

    /**
     * @var Plugin
     */
    private $plugin;

    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function __invoke(Process $process, Batch $batch)
    {
        $iterator = new OrdersFetcherIterator($process, $batch->getApiClient(), $batch->getFsp());

        $settings = $this->plugin->getSettingsForm()->getData();
        $fields = $settings->get('main.fields');

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
                $process->save();
            }
        );

        $writer->close();
        $process->finish((string) $fileUri);
        $process->save();
    }
}