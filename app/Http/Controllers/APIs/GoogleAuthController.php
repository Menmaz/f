<?php

namespace App\Http\Controllers\APIs;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        try {
            //code...
        return Socialite::driver('google')->redirect();
    } catch (\Throwable $th) {
        return response()->json($th->getMessage(), 500);
    }
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        // Lấy thông tin email và mật khẩu từ Google
        $email = $googleUser->getEmail();
        // $password = str_random(12);

        // Kiểm tra xem email đã tồn tại trong hệ thống hay chưa
        $user = User::where('email', $email)->first();

        if (!$user) {
            // Tạo người dùng mới nếu chưa tồn tại
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $email,
                // 'password' => bcrypt($password),
            ]);
        }

        // Đăng nhập người dùng
        Auth::login($user, true);

        // Redirect hoặc trả về JWT token
        // Đây là nơi bạn có thể tạo và trả về JWT token cho người dùng
        // return response()->json(['token' => $token]);
    }
}
