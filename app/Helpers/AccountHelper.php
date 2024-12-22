<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Ophim\Core\Models\LockedUser;

class AccountHelper
{
    //Hàm lưu lại hành động của tài khoản
    public static function captureUserAction($user_id, $action)
    {
        // Tạo một bản ghi mới trong bảng user_actions
        // DB::table('user_actions')->updateOrInsert(
        //     [
        //         'user_id' => $user_id,
        //         'action' => $action,
        //     ],
        //     [
        //         'created_at' => now(),
        //     ]
        // );

        DB::table('user_actions')->insert([
            'user_id' => $user_id,
            'action' => $action,
            'created_at' => now(),
        ]);
    }

    // Hàm kiểm tra xem tài khoản có bị khóa không
    public static function isAccountLocked($user_id)
    {
        $lockRecord = LockedUser::where('user_id', $user_id)->first();
        
        // Nếu có bản ghi và thời gian khóa không null, trả về true
        if ($lockRecord !== null && !is_null($lockRecord->locked_at)) {
            $lockedAt = Carbon::parse($lockRecord->locked_at);
            $threeDaysLater = $lockedAt->addDays(3);

            if (Carbon::now()->greaterThanOrEqualTo($threeDaysLater)) {
                $lockRecord->delete();
                DB::table('user_actions')->where('user_id', $user_id)->delete();
                return false;
            }

            return true;
        }
    
        return false;
    }

    //hàm kiểm tra người dùng có spam hay không, nếu có thì bị khóa
    public static function lockAccountIfSpam($user_id, $action, $threshold, $timeWindowInSeconds)
    {
        // Tính thời gian bắt đầu của khoảng thời gian kiểm tra
        $startTime = Carbon::now()->subSeconds($timeWindowInSeconds);

        // Đếm số lần thực hiện hành động trong khoảng thời gian xác định
        $actionCount = DB::table('user_actions')
            ->where('user_id', $user_id)
            ->where('action', $action)
            ->where('created_at', '>=', $startTime)
            ->count();

        if ($actionCount > $threshold) {
            // Khóa tài khoản nếu vượt ngưỡng spam
            LockedUser::updateOrCreate(
                ['user_id' => $user_id],
                ['locked_at' => Carbon::now()]
            );

            return true;
        }

        return false;
    }
    

    // Hàm kiểm tra nội dung có chứa từ ngữ tục tĩu hoặc liên kết không mong muốn hay không
    public static function isOffensiveOrContainsUnwantedLinks($content)
    {
        // Đọc danh sách từ tục tĩu từ tệp
        $filePath = storage_path('app/vn_offensive_words.txt');
        if (!file_exists($filePath)) {
            throw new \Exception('File chứa danh sách từ tục tĩu không tồn tại');
        }

        $offensiveWords = array_map('trim', file($filePath));

        // Kiểm tra nội dung xem có chứa từ tục tĩu nào không
        foreach ($offensiveWords as $word) {
            if (stripos($content, $word) !== false) {
                return true;  // Nội dung có chứa từ tục tĩu
            }
        }

        // Kiểm tra nội dung xem có chứa liên kết ngoài miền 10truyen.com hay không
        if (preg_match('/http(s)?:\/\/(?!10truyen\.com)/i', $content)) {
            return true;  // Nội dung chứa liên kết không mong muốn
        }

        return false;  // Không chứa từ tục tĩu hoặc liên kết không mong muốn
    }

}
