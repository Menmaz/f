<?php

namespace Ophim\Crawler\OphimCrawler;

use App\Helpers\ImageHelper;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Controllers\Admin\ImageStorageManager;

class Collector
{
    protected $fields;
    protected $payload;
    protected $forceUpdate;

    public function __construct(array $payload, array $fields, $forceUpdate)
    {
        $this->fields = $fields;
        $this->payload = $payload;
        $this->forceUpdate = $forceUpdate;
    }


    //otruyen version
    // public function get(): array
    // {
    //     $dataItem = $this->payload['data']['item'];
    //     $info = $dataItem ?? [];
    //     // $episodes = $this->payload['episodes'] ?? [];

    //     $data = [
    //         'title' => $info['name'],
    //         'origin_name' => $info['origin_name'],
    //         'slug' => $info['slug'],
    //         'description' => $info['content'],
    //         'author' => implode(', ', $info['author']),
    //         // 'type' =>  $this->getMovieType($info, $episodes),
    //         'status' => $info['status'],
    //         'cover' => $this->getThumbImage($info['slug'], 'https://img.otruyenapi.com/uploads/comics/'.$info['thumb_url']),
    //     ];

    //     return $data;
    // }

    public function get(): array
    {
        $dataItem = $this->payload['data']['item'];
        $info = $dataItem ?? [];
        
        // $coverUrl = $this->getThumbImage($info['slug'], 'https://img.otruyenapi.com/uploads/comics/'.$info['thumb_url']);

        // $contaboPrefix = "/uploads/manga/" . $dataItem['slug'] . "/cover.webp";
        // $imageUploader = new ImageStorageManager();
        // $imageData = file_get_contents($coverUrl);
        // $convertedToWebpImage = $imageUploader->convertToWebP($imageData);
        // $uploadImageToContaboUrl = $imageUploader->uploadImageToContabo($contaboPrefix, $convertedToWebpImage);
        // $imageUrl = $uploadImageToContaboUrl;

        $coverUrl = ImageHelper::uploadedMangaThumb($info['slug'], "https://img.otruyenapi.com/uploads/comics/" . $info['thumb_url']);

        $updatedAt = Carbon::parse($info['updatedAt']);

        $data = [
            'title' => $info['name'],
            'origin_name' => $info['origin_name'],
            'slug' => $info['slug'],
            'description' => $info['content'],
            'author' => implode(', ', $info['author']),
            'status' => $info['status'],
            'cover' => $coverUrl,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt
        ];

        return $data;
    }

    public function getThumbImage($slug, $url)
    {
        return $this->getImage(
            $slug,
            $url,
            Option::get('should_resize_thumb', false),
            Option::get('resize_thumb_width'),
            Option::get('resize_thumb_height')
        );
    }

    public function getPosterImage($slug, $url)
    {
        return $this->getImage(
            $slug,
            $url,
            Option::get('should_resize_poster', false),
            Option::get('resize_poster_width'),
            Option::get('resize_poster_height')
        );
    }


    protected function getMovieType($info, $episodes)
    {
        return $info['type'] == 'series' ? 'series'
            : ($info['type'] == 'single' ? 'single'
                : (count(reset($episodes)['server_data'] ?? []) > 1 ? 'series' : 'single'));
    }

    protected function getImage($slug, string $url, $shouldResize = false, $width = null, $height = null): string
    {
        if (!Option::get('download_image', false) || empty($url)) {
            return $url;
        }
        try {
            $url = strtok($url, '?');
            $filename = substr($url, strrpos($url, '/') + 1);
            $path = "images/{$slug}/{$filename}";

            if (Storage::disk('public')->exists($path) && $this->forceUpdate == false) {
                return Storage::url($path);
            }

            // Khởi tạo curl để tải về hình ảnh
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36");
            $image_data = curl_exec($ch);
            curl_close($ch);

            $img = Image::make($image_data);

            if ($shouldResize) {
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            Storage::disk('public')->put($path, null);

            $img->save(storage_path("app/public/" . $path));

            return Storage::url($path);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $url;
        }
    }
}
