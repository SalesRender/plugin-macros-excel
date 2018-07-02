<?php
/**
 * Created for lv-exports.
 * Datetime: 02.07.2018 17:11
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\External\Exports;


class Handler
{

    protected $timezone;
    protected $language;
    protected $config;
    protected $data;

    public function __construct($timezone, $language, $config, $data)
    {
        $this->timezone = $timezone;
        $this->language = $language;
        $this->config = $config;
        $this->data = $data;
    }

}