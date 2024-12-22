<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\Settings\app\Models\Setting;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\Promise;

class ImageStorageManager
{

    public function __construct()
    {
    }

    //chuyển đổi ảnh sang định webp và tối ưu ảnh
    public function convertToWebP($imageData)
    {
        try {
            $image = Image::make($imageData);

            // Nén ảnh với chất lượng 30%
            $image->encode('webp', 30);

            // Lấy dữ liệu ảnh đã nén
            $compressedImageData = $image->encoded;

            // Xóa bộ nhớ đệm của ảnh
            $image->destroy();

            return $compressedImageData;
        } catch (Exception $e) {
            throw new Exception("Failed to convert image to WebP: {$e->getMessage()}");
            return null;
        }
    }

    //upload ảnh vào local
    public function uploadImageToLocal($prefix, $convertedToWebpImage){
        if ($convertedToWebpImage !== false) {
            Storage::disk('public')->put($prefix, $convertedToWebpImage);
            return '/storage/' . $prefix;
        }
    }

    //upload ảnh chapter lên server s3 contabo
    public function uploadImageToContabo($prefix, $convertedToWebpImage){
        $contaboUrl = Setting::where('key', 'contabo_url')->value('value');
        // // $randomString = uniqid();

        // if ($convertedToWebpImage !== false) {
        //     // $filePathOnContabo = "uploads/manga/{$manga->slug}/chapter_$chapter_number/$randomString.webp";
        //     $filePathOnContabo = $prefix;
        //     Storage::disk('contabo')->put($filePathOnContabo, $convertedToWebpImage);
        //     return $contaboUrl . $filePathOnContabo;
        // }
        return $contaboUrl . $prefix;
    }

    //xóa ảnh đã upload trên server s3 contabo
    public function deleteImageOnContabo($prefix){
        //  Storage::disk('contabo')->delete($prefix);
        return true;
    }

    //hàm kiểm tra file có tồn tại trên contabo không
    public function checkExistsOnContabo($prefix){
        // return Storage::disk('contabo')->exists($prefix);
        return true;
    }


    //
    //
    //
    //PIXELDRAIN STORAGE

    //upload ảnh chapter lên server pixeldrain
    public function uploadImageToPixeldrain($prefix, $convertedToWebpImage) {
        $api_key = Setting::where('key', 'pixeldrain_api_key')->value('value');
        $pixeldrain_url = Setting::where('key', 'pixeldrain_url')->value('value');

        if ($convertedToWebpImage !== false) {
        $file_content  = $convertedToWebpImage;
        $url = $pixeldrain_url . $prefix;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode(":$api_key"),
        ]);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($status_code >= 100 && $status_code < 400) {
            $json_response = json_decode($response, true);
            return $pixeldrain_url . '/'. $json_response['id'];
        } else {
            throw new Exception("Upload error. Status: " . $status_code . " Response: " . $response);
        }
    }
    }

    //xóa ảnh đã upload trên server pixeldrain
    public function deleteImageOnPixeldrain($id){
        $api_key = Setting::where('key', 'pixeldrain_api_key')->value('value');
        $pixeldrain_url = Setting::where('key', 'pixeldrain_url')->value('value');

        $url = $pixeldrain_url . $id;
        $headers = @get_headers($url);
        if ($headers && strpos($headers[0], '200') !== false) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic " . base64_encode(":$api_key"),
        ]);

        $response = curl_exec($ch);
        $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($status_code >= 100 && $status_code < 400) {
            return response(['message' => 'Delete image successfully !']);
        } else {
            throw new Exception("Delete image error. Status: " . $status_code . " Response: " . $response);
        }
    }
    }

    
    //
    //
    //
    //BACKBLAZE STORAGE

    //upload ảnh chapter lên server s3 backblaze
    public function uploadImageToBackblaze($prefix, $convertedToWebpImage){
        $backblazeUrl = Cache::remember('backblaze_image_domain', 60 * 60 * 24, function () {
            return Setting::where('key', 'backblaze_image_domain')->value('value');
        });
        
        if ($convertedToWebpImage !== false) {
            $options = [
                'ACL' => 'public-read', 
                'Cache-Control' => 'max-age=31536000', // Thiết lập cache trong 1 năm
                'Content-Type' => 'image/webp', // Đảm bảo loại nội dung là webp
            ];

        Storage::disk('backblaze')->put($prefix, $convertedToWebpImage, $options);
        return $backblazeUrl . "/file/10truyen" . $prefix;
                // $url = Storage::disk('backblaze')->url($prefix);
            // return $url; // Trả về URL công khai của tệp tin
        }
    }

    //xóa ảnh đã upload trên server s3 backblaze
    public function deleteImageOnBackblaze($prefix){
         Storage::disk('backblaze')->delete($prefix);
    }

    //hàm kiểm tra file có tồn tại trên backblaze không
    public function checkExistsOnBackblaze($prefix){
        return Storage::disk('backblaze')->exists($prefix);
    }

    //
    //
    //imgthumb.giatot.xyz
    //
    //
    
}
