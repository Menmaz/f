<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Setting;

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'seoData' => $this->getSeoData(),
            'sliderMangas' => $this->getSliderMangas(),
            'notication_message' => Setting::where('key', 'notifications')->value('value'),
            'popularDayMangas' => $this->getPopularMangas('day'),
            'popularWeekMangas' =>   $this->getPopularMangas('week'),
            'popularMonthMangas' =>   $this->getPopularMangas('month'),
            'allUpdatedMangas' => $this->getUpdatedMangas(),
            'updatedMangas' => $this->getUpdatedMangas('manga'),
            'updatedManhuas' => $this->getUpdatedMangas('manhua'),
            'updatedManhwas' => $this->getUpdatedMangas('manhwa'),
            'latestMangas' => $this->getLatestMangas()
           ];

           return view('frontend-web.home.index')->with($data);
        // return response()->json($data);
    }

    protected function getSliderMangas(){
            return Manga::where('is_recommended', true)
            ->select(['id', 'title', 'slug', 'cover', 'description'])
            ->with([
                'taxanomies' => function ($query) {
                return $query->whereIn('type', ['genre', 'status'])
                    ->select(['name', 'slug', 'type']);
                },
            ])
            ->withMax('chapters', 'chapter_number')
            ->take(10)
            ->get();
    }

    protected function getUpdatedMangas($type = null){
        return Cache::remember('updated_mangas_' . ($type ?: 'all'), now()->addMinutes(30), function () use ($type) {
            $query = Manga::orderBy('updated_at', 'desc')
            ->select(['id', 'title', 'slug', 'cover'])
            ->with([
                'taxanomies' => function ($query) {
                return $query->select('name', 'slug', 'type');
                },
                'chapters' => function ($query) {
                return $query
                    ->orderBy('chapter_number', 'desc')
                    ->select('manga_id', 'chapter_number', 'created_at')
                    ->limit(3);
                }
            ])
            ->withAvg('starRatings', 'rating')
            ->withCount('starRatings')
            ->withSum('views', 'views');

            if ($type) {
                $query->whereHas('taxanomies', function ($query) use ($type) {
                    return $query->where('slug', $type);
                });
            }

            return $query->paginate(18);
        
        });
    }

    public function getLatestMangas()
    {
        return Cache::remember('latest_mangas', now()->addMinutes(30), function () {
            return Manga::latest()
            ->select(['title', 'slug', 'cover'])
            ->withSum('views', 'views')
            ->limit(20)->get();
        });
    }

    protected function getMangas($orderByColumn, $limit = 20, $type = null, $paginate = false)
    {
        $query = Manga::
            whereHas('taxanomies', function ($query) use ($type) {
            if ($type) {
                $query->where('slug', $type);
            }
        })
        ->orderBy($orderByColumn, 'desc')
        ->select(['id', 'title', 'slug', 'cover']);

        if ($type) {
            $query->whereHas('taxanomies', function ($query) use ($type) {
                return $query->where('slug', $type);
            });
        }

        if ($paginate) {
            return $query->paginate($limit);
        }

        return $query->take($limit)->get();
    }

   
        public function getPopularMangas($timeType)
    {
        return Cache::remember('popular_mangas_' . $timeType, now()->addHours(8), function () use ($timeType) {
        $now = Carbon::now();
        switch ($timeType) {
            case 'day':
                $startDate = $now->startOfDay();
                break;
            case 'week':
                $startDate = $now->startOfWeek(); // Đầu tuần
                break;
            case 'month':
                $startDate = $now->startOfMonth(); // Đầu tháng
                break;
            default:
                $startDate = $now->startOfDay();
        }

        $mangas = Manga::select(['title', 'slug', 'cover'])
        ->whereHas('views', function ($query) use ($startDate) {
            $query->where('updated_at', '>=', $startDate);
        })
        ->withSum('views', 'views')
        ->orderByDesc('views_sum_views')
        ->take(10)->get();

        return $mangas;
        });
    }

    public function search(Request $request){
        $keyword = $request->keyword;
        $words = explode(' ', $keyword);
        $mangas = Manga::where(function ($query) use ($keyword, $words) {
            $query->orWhere(function ($innerQuery) use ($keyword) {
                $innerQuery->where('title', 'LIKE', $keyword . '%')
                    ->orWhere('alternative_titles', 'LIKE', $keyword . '%')
                    ->orwhere("description", "like", "%" . $keyword . "%")
                    ->orWhere('slug', 'LIKE', $keyword . '%');
            });

            foreach ($words as $word) {
                $query->orWhere('title', 'LIKE', '%' . $word . '%');
            }
        })
        ->orderBy(function ($query) use ($keyword) {
            return $query->selectRaw("CASE WHEN title LIKE '{$keyword}%' THEN 0 ELSE 1 END");
        })
        ->select(['id', 'title', 'slug', 'cover'])
        ->withMax('chapters', 'chapter_number')
        ->with('statuses:name')
        ->take(5)->get();
        return response()->json($mangas);
    }

    protected function getSeoData()
    {
        $settings = $this->getSeoSettings();
        $seoData['title'] = $settings->get('site_meta_siteName')->value;
        $seoData['icon'] = asset($settings->get('site_meta_shortcut_icon')->value);
        $seoData['description_meta'] = $settings->get('site_meta_description')->value;
        $seoData['keywords_meta'] = $settings->get('site_meta_keywords')->value;
        $seoData['image_meta'] = $settings->get('site_meta_image')->value;
        $seoData['head_tags_meta'] = $settings->get('site_meta_head_tags')->value;
        $seoData['site_script'] = $settings->get('site_scripts_google_analytics')->value;

        return $seoData;
    }

    protected function getNotificationMessage()
    {
        return Cache::rememberForever('notification_message', function () {
            return Setting::where('key', 'notifications')->value('value');
        });
    }

    public function sendMangaRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'message' => 'required|string',
            'g-recaptcha-response' => 'required'
        ], [
            'title.required' => 'Tên người dùng là bắt buộc.',
            'title.string' => 'Tên người dùng phải là một chuỗi ký tự.',
            'message.required' => 'Mật khẩu là bắt buộc.',
            'message.string' => 'Mật khẩu phải là một chuỗi ký tự.',
            'g-recaptcha-response.required' => 'Vui lòng xác thực reCAPTCHA.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        // Verify reCAPTCHA
        // $recaptchaSecret = config('custom.recapcha_secret_key');
        // $recaptchaResponse = $request->input('g-recaptcha-response');

        // $client = new Client();
        // $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
        //     'form_params' => [
        //         'secret' => $recaptchaSecret,
        //         'response' => $recaptchaResponse
        //     ]
        // ]);

        // $body = json_decode((string)$response->getBody());

        // if (!$body->success) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Xác thực reCAPTCHA không thành công !'
        //     ]);
        // }

        DB::table('manga_requests')->insert([
            'manga_title' => $request->input('title'),
            'message' => $request->input('message'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Gửi yêu cầu thành công',
        ]);
    }

}
