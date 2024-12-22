<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\Badge;

class UserResource extends JsonResource
{

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'avatar' => $this->avatar ?? 'https://media.istockphoto.com/id/1337144146/vector/default-avatar-profile-icon-vector.jpg?s=612x612&w=0&k=20&c=BIbFwuv7FxTWvh5S3vB6bkT0Qv8Vn8N5Ffseq84ClGI=',
            'description' => $this->description ?? '',
            'email' => $this->email ?? '',
            'rule' => ($this->admin == 1) ? 'admin' : 'user',
            'badge' => $this->getBadgeInfo(),
        ];
    
    }

function getBadgeInfo()
{
    $userHasBadge = DB::table('users_has_badge')->where('user_id', $this->id)->first();
    
    $currentBadge = null;

    $badges = []; // Mảng để chứa các huy hiệu

    if($userHasBadge){
        $badgeId = $userHasBadge->badge_id; 
        $currentBadge = Badge::where('id', $badgeId)->first();
        if($currentBadge){
            $badges[] = [
            'name' => $currentBadge->name,
            'cssColor' => $currentBadge->css_color,
            'progress' => 0,
            'totalPoints' => 0,
        ];   
        }
        
    } else {
        // Tính tổng số lượt xem của người dùng
        $totalPoints = $this->views->count();

        // Truy vấn tất cả các badge để có thông tin liên quan
        $allBadges = Badge::whereNotNull('comment_threshold')
        ->orderBy('comment_threshold', 'asc')->get();

        if ($allBadges->isEmpty()) {
            return [
                'badges' => [], // Không có huy hiệu nào
            ];
        }

        // Tìm badge hiện tại
        $currentBadge = null;
        foreach ($allBadges as $badge) {
            if ($badge->comment_threshold > $totalPoints) {
                $currentBadge = $badge;
                break;
            }
        }

        // Nếu không có badge hiện tại, lấy badge đầu tiên
        if (!$currentBadge) {
            $currentBadge = $allBadges->first();
        }

        // Tính tiến trình
        $progress = 0;
        if ($currentBadge->comment_threshold > 0) {
            // $progress = min(max(($totalPoints / $currentBadge->comment_threshold) * 100, 0), 100); // Tính tiến trình dưới dạng tỷ lệ phần trăm
            $progress = min(($totalPoints / $currentBadge->comment_threshold) * 100, 99); 
        }

        // Thêm badge hiện tại vào mảng
        $badges[] = [
            'name' => $currentBadge->name,
            'cssColor' => $currentBadge->css_color,
            'progress' => $progress,
            'totalPoints' => $totalPoints,
        ];   
    }

    return $badges;
}



}
