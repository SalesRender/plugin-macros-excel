<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 20.06.2019 17:38
 */

namespace Leadvertex\External\Export\App\Components;


class ApiParams
{

    /**
     * @var string
     */
    private $token;
    /**
     * @var string
     */
    private $endpointUrl;

    public function __construct(string $token, string $endpointUrl)
    {
        $this->token = $token;
        $this->endpointUrl = $endpointUrl;
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return string
     */
    public function getEndpointUrl(): string
    {
        return $this->endpointUrl;
    }

}