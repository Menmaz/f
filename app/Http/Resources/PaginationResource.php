<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

// class PaginationResource extends JsonResource
// {
//     public function toArray($request)
//     {
        
//         return [
//             'data' =>$this->resource->items(),
//             'pagination' => [
//                 'totalItems' => $this->resource->total(),
//                 'totalItemsPerPage' => $this->resource->perPage(),
//                 'currentPage' => $this->resource->currentPage(),
//                 'totalPages' => $this->resource->lastPage(),
//             ],
//         ];
//     }
// }

class PaginationResource extends JsonResource
{
    public function toArray($request)
    {
        $data = $this['data'];
        $seoOnPage = $this['seoOnPage'];
        
        return [
            'data' => $data,
            'pagination' => [
                'totalItems' => $data->total(),
                'totalItemsPerPage' => $data->perPage(),
                'currentPage' => $data->currentPage(),
                'totalPages' => $data->lastPage(),
            ],
            'seoOnPage' => $seoOnPage,
        ];
    }
}
