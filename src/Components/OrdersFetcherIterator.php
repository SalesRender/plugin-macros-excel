<?php
/**
 * Created for plugin-exporter-excel
 * Datetime: 30.07.2019 16:20
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Instance\Excel\Components;


use Leadvertex\Plugin\Components\ApiClient\ApiFetcherIterator;
use XAKEPEHOK\ArrayGraphQL\ArrayGraphQL;
use XAKEPEHOK\ArrayGraphQL\InvalidArrayException;

class OrdersFetcherIterator extends ApiFetcherIterator
{

    /**
     * @param array $fields
     * @return string
     * @throws InvalidArrayException
     */
    protected function getQuery(array $fields): string
    {
        return '
            query($pagination: Pagination!, $filters: OrderSearchFilter, $sort: OrderSort) {
                ordersFetcher(pagination: $pagination, filters: $filters, sort: $sort) ' . ArrayGraphQL::convert($fields) . '
            }
        ';
    }

    /**
     * Dot-notation string to query body
     * @return string
     */
    protected function getQueryPath(): string
    {
        return 'ordersFetcher';
    }

    protected function getIdentity(array $array): string
    {
        return $array['id'];
    }
}