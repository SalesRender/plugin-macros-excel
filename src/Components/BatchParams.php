<?php
/**
 * @author Timur Kasumov (aka XAKEPEHOK)
 * Datetime: 21.06.2019 22:02
 */

namespace Leadvertex\External\Export\App\Components;


class BatchParams
{

    /**
     * @var string
     */
    private $token;
    /**
     * @var string
     */
    private $successWebhookUrl;
    /**
     * @var string
     */
    private $failsWebhookUrl;
    /**
     * @var string
     */
    private $resultWebhookUrl;
    /**
     * @var string
     */
    private $errorWebhookUrl;

    public function __construct(
        string $token,
        string $successWebhookUrl,
        string $failsWebhookUrl,
        string $resultWebhookUrl,
        string $errorWebhookUrl
    )
    {
        $this->token = $token;
        $this->successWebhookUrl = $successWebhookUrl;
        $this->failsWebhookUrl = $failsWebhookUrl;
        $this->resultWebhookUrl = $resultWebhookUrl;
        $this->errorWebhookUrl = $errorWebhookUrl;
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
    public function getSuccessWebhookUrl(): string
    {
        return $this->successWebhookUrl;
    }

    /**
     * @return string
     */
    public function getFailsWebhookUrl(): string
    {
        return $this->failsWebhookUrl;
    }

    /**
     * @return string
     */
    public function getResultWebhookUrl(): string
    {
        return $this->resultWebhookUrl;
    }

    /**
     * @return string
     */
    public function getErrorWebhookUrl(): string
    {
        return $this->errorWebhookUrl;
    }

}