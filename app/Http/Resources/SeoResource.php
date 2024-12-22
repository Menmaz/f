<?php

namespace App\Http\Resources;

use Backpack\Settings\app\Models\Setting;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class SeoResource extends JsonResource
{
    protected $seoData;

    public function __construct($resource, $seoData = [])
    {
        parent::__construct($resource);
        $this->seoData = $seoData;
    }

    public function toArray($request)
    {
        $settings = Cache::rememberForever('site_settings', function () {
            return Setting::whereIn('key', [
                'site_meta_siteName',
                'site_meta_description',
                'site_meta_keywords',
                'site_meta_shortcut_icon',
                'site_meta_image',
                'site_meta_head_tags',
                'site_scripts_google_analytics'
            ])->get()->keyBy('key');
        });

        $titleHead = str_replace('{current_year}', date('Y'), $this->seoData['titleHead'] ?? $settings->get('site_meta_siteName')->value);
        
        $data = [
            'titleHead' => $titleHead,
            'descriptionHead' => $this->getdescriptionHead($this->seoData['descriptionHead'] ?? $settings->get('site_meta_description')->value),
            'keywordsHead' => !empty($this->seoData['keywordsHead'])
                              ? explode(', ', $this->seoData['keywordsHead'])
                              : explode(', ', $settings->get('site_meta_keywords')->value),
            'shortcutIconHead' => asset($settings->get('site_meta_shortcut_icon')->value) ?? '',
            'tagsHead' => $this->getTagsHead($settings->get('site_meta_head_tags')->value, $settings->get('site_scripts_google_analytics')->value),
            'og_type' => $this->seoData['og_type'] ?? 'website',
            'og_image' => !empty($this->seoData['og_image']) 
                        ? implode(', ', (array) $this->seoData['og_image']) 
                        : implode(', ', (array) asset($settings->get('site_meta_image')->value)),
            'og_url' => $this->seoData['og_url'] ?? 'https://10truyen.com',
            'seoSchema' => [
                "@context" => "https://schema.org",
                "@type" => "website",
                "name" => $titleHead,
                "url" => $this->seoData['og_url'] ?? 'https://10truyen.com',
                "image" => $this->seoData['seoSchema']['image'] ?? asset($settings->get('site_meta_image')->value),
                "director" => $this->seoData['seoSchema']['director'] ?? "Đang cập nhật",
            ]
        ];

        return array_merge($data, parent::toArray($request));
    }

    protected function getDescriptionHead($desciptionHead) {
        $plainText = strip_tags($desciptionHead);
    
        if (!mb_check_encoding($plainText, 'UTF-8')) {
            $plainText = mb_convert_encoding($plainText, 'UTF-8', 'auto');
        }

        $maxLength = 156; 
    
        if (mb_strlen($plainText) > $maxLength) {
            $truncatedText = mb_substr($plainText, 0, $maxLength); 
            $truncatedText = rtrim($truncatedText);
            $truncatedText .= '...';
        } else {
            $truncatedText = $plainText;
        }
    
        return mb_substr($truncatedText, 0, 159);
    }

    protected function getTagsHead($tagHeadsHtml, $scriptTagsHtml){
        $metaTags = explode("\r\n", $tagHeadsHtml);
        $scriptTags = explode("\r\n", $scriptTagsHtml);
        return array_merge($metaTags, $scriptTags) ?? [];
    }
}
