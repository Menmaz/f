<?php

namespace App\Helpers; // hoặc tên namespace phù hợp với ứng dụng của bạn

use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Illuminate\Support\Facades\DB;

class ImageHelper
{
    public static function optimizeImageUrl($url)
    {
        try {
            // Lấy ảnh từ URL
            // $response = Http::get($url);
            $response = Http::timeout(30)->get($url);

            if ($response->failed()) {
                throw new Exception("Failed to fetch image from URL");
            }

            $imageContent = $response->body(); // Nội dung ảnh

            // Kiểm tra kích thước ảnh trước khi xử lý
            $size = strlen($imageContent);
            if ($size > 1024 * 1024 * 2) { // Giới hạn 2 MB
                return $url;
            }

            // Chuyển đổi ảnh sang WebP
            $image = Image::make($imageContent)->encode('webp', 45);
            $imageData = (string) $image;

            // Trả về nội dung ảnh WebP
            return response($imageData, Response::HTTP_OK, [
                'Content-Type' => 'image/webp',
            ]);
        } catch (Exception $e) {
            // Xử lý ngoại lệ
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } finally {
            // Giải phóng bộ nhớ hoặc tài nguyên
            if (isset($image)) {
                unset($image); // Giải phóng bộ nhớ
            }

            gc_collect_cycles(); // Giải phóng bộ nhớ bổ sung
        }
    }

    public static function uploadedMangaThumb($mangaSlug, $mangaCover){
        $optimizedMangaData = self::optimizeImageUrl($mangaCover);

        if ($optimizedMangaData instanceof \Illuminate\Http\JsonResponse) {
            return $mangaCover;
        }
        
        if ($optimizedMangaData instanceof \Illuminate\Http\Response) {
            $optimizedMangaData = $optimizedMangaData->getContent();
        } else {
            return $mangaCover;
        }

        // $response = Http::asMultipart() 
        // ->attach('image_data', $optimizedMangaData, 'image.webp')
        //   ->post('https://imgthumb.giatot.xyz', [
        //       'manga_slug' => $mangaSlug, 
        //   ]);

        // if ($response->successful()) {
        //     $uploadedImageUrl = 'https://imgthumb.giatot.xyz/image_thumbs/' . $mangaSlug . '_thumb.webp';
        //     return $uploadedImageUrl;
        // } else {
        //     return $mangaCover;
        // }
        $fileName = $mangaSlug . '_thumb.webp';
        $filePath = 'public/user_avatars/' . $fileName;
        Storage::put($filePath, $optimizedMangaData);
        // Return the URL to the stored image
        return Storage::url($filePath);
    }

    public static function uploadedUserAvatar($userId, $userName, $userAvatar){
        $timestamp = time();
        $userId = base64_encode($userId);

        // Load ảnh từ đường dẫn
        $image = Image::make($userAvatar);

        // Giảm chất lượng của ảnh
        $image->encode('webp', 50); 

        // Lưu ảnh lại với chất lượng giảm
        $tempImagePath = tempnam(sys_get_temp_dir(), 'avatar_') . '.webp';
        $image->save($tempImagePath);

        $response = Http::asMultipart() 
        ->attach('image_data', fopen($tempImagePath, 'r'), 'avatar.webp')
          ->post('https://imgthumb.giatot.xyz', [
              'user_id' => $userId,
              'username' => $userName, 
              'timestamp' => $timestamp,
          ]);

          // Xóa tệp tạm sau khi sử dụng
          unlink($tempImagePath);

        if ($response->successful()) {
            $uploadedImageUrl = 'https://imgthumb.giatot.xyz/users/avatars/' . $userName . '_' . $userId . '_' . $timestamp . '.webp';
            return $uploadedImageUrl;
        } else {
            return $userAvatar;
        }
    }

    public static function getImageUrlAfterByPass($imageUrl, $referrer){
        try {
            $ch = curl_init();
    
            curl_setopt($ch, CURLOPT_URL, $imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Referer: ' . $referrer,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]);

            $imageData = curl_exec($ch);
            curl_close($ch);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            
            return response($imageData)->header('Content-Type', $contentType);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
