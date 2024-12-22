<?php

namespace Ophim\Crawler\OphimCrawler\Contracts;

use GuzzleHttp\Client;

abstract class BaseCrawler
{
    protected $link;
    protected $fields;
    protected $excludedCategories;
    protected $excludedRegions;
    protected $excludedType;
    protected $forceUpdate;
    protected $contextConnection;
    protected $client;
    protected $logger;

    public function __construct($link, $fields, $excludedCategories = [], $excludedType = [], $forceUpdate, $logger)
    {
        $this->link = $link;
        $this->fields = $fields;
        $this->excludedCategories = $excludedCategories;
        $this->excludedType = $excludedType;
        $this->forceUpdate = $forceUpdate;
        $this->contextConnection = stream_context_create([
            'http' => [
                'timeout' => 30, // Adjust the timeout value as needed
            ],
            'ssl' => [
                'timeout' => 30, // Adjust the SSL timeout value as needed
            ],
        ]);
        // $this->client = new Client();
        $this->client = new Client([
            'timeout' => 30, // Timeout cho tất cả các yêu cầu HTTP
            'connect_timeout' => 30, // Timeout cho quá trình kết nối
            'verify' => false, // Tắt xác thực SSL (chỉ dùng cho môi trường phát triển)
            'max_connections' => 5, // Số lượng kết nối tối đa
            'keep_alive' => true, // Tái sử dụng kết nối
        ]);
        $this->logger = $logger;
    }

    abstract public function handle();
}
