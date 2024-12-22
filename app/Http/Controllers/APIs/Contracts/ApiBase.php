<?php
namespace App\Http\Controllers\APIs\Contracts;

use Illuminate\Routing\Controller as BaseController;

abstract class ApiBase extends BaseController
{
   
    protected function response($data = [], $status = 200,array $headers = [])
    {
        if($status == 200){
            $startTimestamp = defined('LARAVEL_START') ? LARAVEL_START : 0;
            $endTimestamp = microtime(true);
            $executionTime = $endTimestamp - $startTimestamp;
            return response()->json( [
                "status" => "success",
                "status_code" => $status,
                "message" => $data["message"] ?? "",
                "csrf_token" => $data["csrf_token"] ?? "",
                "data" => $data["data"] ?? [],
                "APP_DOMAIN_FRONTEND" => settings("APP_DOMAIN_FRONTEND",""),
                "APP_DOMAIN_CDN_IMAGE" => settings("APP_DOMAIN_CDN_IMAGE",""),
                "startTimestamp" => $startTimestamp,
                "endTimestamp" => $endTimestamp,
                "executionTime" => $executionTime,
                "version" => 1
            ], $status, $headers);
        } else {
            $errorMessage = $data['message'];
        
            if ($status == 500) {
                $errorMessage = "Lỗi hệ thống"; // Custom error message for 500 status
            }
            return response()->json(
                [
                    "status" => false,
                    "status_code" => $status,
                    "message" => $errorMessage,
                ],
                $status,
                $headers
            );
        }  
    }


    //phan trang - pagination data
    protected function parsePagination($paginatedData) {
        $perPage = intval($paginatedData->perPage());
        $currentPage = intval($paginatedData->currentPage());
        $total = intval($paginatedData->total());

        $from = ($currentPage - 1) * $perPage + 1; 
        $to = min($currentPage * $perPage, $total); 

        return [
            "currentPage" => $currentPage ?? '',
            "first_page_url" => $paginatedData->url(1) ?? '',
            "lastPage" => intval($paginatedData->lastPage()) ?? '',
            "last_page_url" => $paginatedData->url($paginatedData->lastPage()) ?? '',
            "links" => $paginatedData->toArray()['links'] ?? [],
            "next_page_url" => $paginatedData->nextPageUrl() ?? '',
            "path" => $paginatedData->path() ?? '',
            "per_page" => $perPage ?? '',
            "prev_page_url" => $paginatedData->previousPageUrl() ?? '',
            "total" => $total,
            "from" => $from, 
            "to" => $to,
        ];
    }


}
