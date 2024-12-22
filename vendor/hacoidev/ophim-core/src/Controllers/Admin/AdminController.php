<?php

namespace Ophim\Core\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Helpers\UserHelper;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\ChapterReport;
use Ophim\Core\Models\User;
use Ophim\Core\Models\View;

class AdminController extends Controller
{
    protected $data = []; // the information we send to the view

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware(backpack_middleware());
    }

    /**
     * Show the admin dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        try {
            //code...
        UserHelper::checkAdminPermissions();

return view(backpack_view('dashboard'));
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
    }

    public function getDashboardCounterInfo()
{
    $cacheKey = 'dashboard_counter_info';

    $data = Cache::remember($cacheKey, 300, function () {
        $results = DB::select("
            SELECT 'count_movies' AS type, COUNT(*) AS count FROM mangas
            UNION ALL
            SELECT 'count_episodes' AS type, COUNT(*) AS count FROM chapters
            UNION ALL
            SELECT 'count_total_views' AS type, COALESCE(SUM(views), 0) AS count FROM views
            UNION ALL
            SELECT 'count_episodes_error' AS type, COUNT(*) AS count FROM chapter_reports
            UNION ALL
            SELECT 'count_users' AS type, COUNT(*) AS count FROM users
        ");

        $data = collect($results)->mapWithKeys(function ($result) {
            return [$result->type => $result->count];
        });

        $chapterCounts = Chapter::whereIn('status', ['waiting_to_upload', 'uploaded_to_storage'])
            ->groupBy('status')
            ->selectRaw('status, COUNT(*) as count')
            ->get()
            ->keyBy('status');

        $countWaitingToUpload = $chapterCounts->get('waiting_to_upload', (object)['count' => 0])->count;
        $countUploadedToStorage = $chapterCounts->get('uploaded_to_storage', (object)['count' => 0])->count;

        $data['count_waiting_to_upload_chapters'] = $countWaitingToUpload;
        $data['count_uploaded_to_storage_chapters'] = $countUploadedToStorage;

        return $data;
    });

    return response()->json($data);
}

    public function getDashboardMangasInfo(){
        try {
        $this->data['top_view_day'] = $this->getTopMangas('day');
        $this->data['top_view_week'] = $this->getTopMangas('week');
        $this->data['top_view_month'] = $this->getTopMangas('month');
        $this->data['top_view_year'] = $this->getTopMangas('year');
        $this->data['top_view_all'] = $this->getTopMangas('all');

        return response()->json($this->data);
        
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
    }

    public function getTopMangas($timeType)
{
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
        case 'month':
            $startDate = $now->startOfYear(); // Đầu năm
            break;
        default:
            $startDate = Carbon::createFromDate(1970, 1, 1);
    }

    // $mangas = Manga::select(
    //     'mangas.title', 
    //     'mangas.slug', 
    //     'mangas.cover',
    //     'mangas.created_at', 
    //     'mangas.updated_at', 
    //     DB::raw('SUM(views.views) as views')
    // )
    // ->join('views', 'mangas.id', '=', 'views.key') // Liên kết bảng views với mangas
    // ->where('views.created_at', '>=', $startDate) // Chỉ lấy dữ liệu từ khoảng thời gian đã chỉ định
    // ->groupBy('mangas.id', 'mangas.title', 'mangas.slug', 'mangas.cover', 'mangas.created_at', 'mangas.updated_at')
    // ->orderByDesc('views') // Sắp xếp theo tổng lượt xem
    // ->limit(10)
    // ->get();

    $mangas = Manga::select(
                'mangas.title', 
                'mangas.slug', 
                DB::raw('SUM(views.views) as views')
            )
            ->join('views', 'mangas.id', '=', 'views.key')
            ->where('views.updated_at', '>=', $startDate) 
            ->groupBy('mangas.id', 'mangas.title', 'mangas.slug')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

    return $mangas;
}


    /**
     * Redirect to the dashboard.
     *
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function redirect()
    {
        // The '/admin' route is not to be used as a page, because it breaks the menu's active state.
        return redirect(backpack_url('dashboard'));
    }
}
