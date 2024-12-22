<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Notification;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    protected function verifyRecaptcha($recaptchaResponse, $request)
    {
        $recaptchaSecret = config('custom.recapcha_secret_key');
        $recaptchaResponse = $request->input('g-recaptcha-response');

        $client = new Client();
        $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $recaptchaSecret,
                'response' => $recaptchaResponse
            ]
        ]);

        $body = json_decode((string)$response->getBody());

        return $body->success;
    }

    public function login(Request $request)
    {
        $credentials = $request->only('username', 'password');

        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required'
        ], [
            'username.required' => 'Tên người dùng là bắt buộc.',
            'username.string' => 'Tên người dùng phải là một chuỗi ký tự.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.string' => 'Mật khẩu phải là một chuỗi ký tự.',
            'g-recaptcha-response.required' => 'Vui lòng xác thực reCAPTCHA.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        // Verify reCAPTCHA
        // if (!$this->verifyRecaptcha($request->input('g-recaptcha-response'), $request)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Xác thực reCAPTCHA không thành công !'
        //     ]);
        // }

        $credentials = $request->only('username', 'password');
        if (Auth::attempt(['email' => $credentials['username'], 'password' => $credentials['password']]) ||
            Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            
            // Check and create notifications for new chapters
            $this->checkAndCreateNotifications();

            return response()->json([
                'status' => 'success',
                'message' => 'Đăng nhập thành công',
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Thông tin đăng nhập không chính xác'
        ]);
    }

    private function checkAndCreateNotifications()
    {
        $currentTime = now();
        $user = auth()->user();
        $bookmarks = $user->bookmarks->where('bookmarkable_type', Manga::class)->pluck('bookmarkable_id');

        foreach ($bookmarks as $mangaId) {
            $newChapter = Chapter::where('manga_id', $mangaId)
                ->latest()
                ->first();

            if ($newChapter && $newChapter->created_at > $currentTime) {
                // Check if notification already exists
                $existingNotification = Notification::where('user_id', $user->id)
                    ->where('manga_id', $mangaId)
                    ->where('message', 'LIKE', 'Đã có chapter mới%')
                    ->first();

                if (!$existingNotification) {
                    // Create new notification
                    Notification::create([
                        'user_id' => $user->id,
                        'manga_id' => $mangaId,
                        'message' => 'Đã có chapter mới: Chapter ' . $newChapter->chapter_number,
                    ]);
                }
            }
        }
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
            $validator = Validator::make($request->all(), [
                'username' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'g-recaptcha-response' => 'required'
            ], [
                'username.required' => 'Tên người dùng là bắt buộc.',
                'username.string' => 'Tên người dùng phải là chuỗi ký tự.',
                'username.max' => 'Tên người dùng không được vượt quá 255 ký tự.',
                'email.required' => 'Email là bắt buộc.',
                'email.string' => 'Email phải là chuỗi ký tự.',
                'email.email' => 'Email không hợp lệ.',
                'email.max' => 'Email không được vượt quá 255 ký tự.',
                'email.unique' => 'Email đã tồn tại.',
                'password.required' => 'Mật khẩu là bắt buộc.',
                'password.string' => 'Mật khẩu phải là chuỗi ký tự.',
                'password.min' => 'Mật khẩu ít nhất phải có 8 ký tự.',
                'password.confirmed' => 'Mật khẩu không khớp với xác nhận mật khẩu.',
                'g-recaptcha-response.required' => 'Vui lòng xác thực reCAPTCHA.'
            ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

        // Verify reCAPTCHA
        //  if (!$this->verifyRecaptcha($request->input('g-recaptcha-response'), $request)) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Xác thực reCAPTCHA không thành công !'
        //     ]);
        // }

        User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // $request->session()->regenerate();
        return response()->json([
            'status' => 'success',
            'message' => 'Đăng ký thành công, hãy đăng nhập',
            'redirect' => url('/')
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->back();
    }


}
