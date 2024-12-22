<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Cache;
use Ophim\Core\Models\Setting;

class Controller extends BaseController
{
 use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function __construct()
    {
    }

    protected function getSeoSettings()
    {
        return Cache::rememberForever('settings', function () {
            return Setting::whereIn('key', [
                'site_meta_siteName',
                'site_meta_shortcut_icon',
                'site_homepage_title',
                'site_meta_description',
                'site_meta_keywords',
                'site_meta_image',
                'site_meta_head_tags',
                'site_scripts_google_analytics',
                'site_movie_title',
                'site_episode_watch_title',
                'site_tag_title',
                'site_tag_des',
                'site_tag_key'
            ])->get()->keyBy('key');
        });
    }
}
