<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.06.2019 17:47
 */

namespace Leadvertex\External\Export\App\Components;


class GenerateParams
{
    /**
     * @var StoredConfig
     */
    private $config;
    /**
     * @var BatchParams
     */
    private $batchParams;
    /**
     * @var ChunkedIds
     */
    private $chunkedIds;

    public function __construct(
        StoredConfig $config,
        BatchParams $batchParams,
        ChunkedIds $chunkedIds
    )
    {
        $this->config = $config;
        $this->batchParams = $batchParams;
        $this->chunkedIds = $chunkedIds;
    }

    /**
     * @return StoredConfig
     */
    public function getConfig(): StoredConfig
    {
        return $this->config;
    }

    /**
     * @return BatchParams
     */
    public function getBatchParams(): BatchParams
    {
        return $this->batchParams;
    }

    /**
     * @return ChunkedIds
     */
    public function getChunkedIds(): ChunkedIds
    {
        return $this->chunkedIds;
    }


}