<?php

namespace App\Http\Controllers\APIs;

use App\Helpers\ImageHelper;
use App\Http\Controllers\APIs\Contracts\ApiBase ;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Models\Comment;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;

class CommentController extends ApiBase
{
    public function __construct()
    {
        
    }

    //hàm tối ưu link ảnh (thử nghiệm)
    public function optimizeImageUrl(Request $request)
{
    try {
        $url = $request->input('url'); 
        $imageData = ImageHelper::optimizeImageUrl($url);

        if ($imageData instanceof \Illuminate\Http\Response) {
            // Nếu đây là phản hồi HTTP, bạn cần lấy nội dung bên trong
            $imageData = $imageData->getContent();  // Lấy nội dung nhị phân của hình ảnh
        }
        
        if (empty($imageData)) {
            return 'Image data is empty. Please check the source.';
        }
        $response = Http::asMultipart() 
                    ->attach('image_data', $imageData, 'image.webp')
                      ->post('https://imgthumb.giatot.xyz', [
                          'manga_slug' => 'test', 
                      ]);

        // Kiểm tra phản hồi và trả về kết quả
        if ($response->successful()) {
            return 'Image has been successfully uploaded and optimized';
        } else {
            return 'Error in optimizing image';
        }
    } catch (Exception $e) {
        return 'Error in getting cover';
    } 
}

}
