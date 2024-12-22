<?php

namespace App\Http\Controllers\APIs\Auth;

use App\Http\Controllers\APIs\Contracts\ApiBase;
use App\Http\Controllers\Controller;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use App\Models\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Illuminate\Support\Facades\View;

class ResetPasswordController extends ApiBase
{
//     public function sendResetLinkEmail(Request $request)
// {
//     $request->validate(['email' => 'required|email']);

//     $email = $request->input('email');

//     // Kiểm tra xem email có tồn tại trong hệ thống không
//     $user = User::where('email', $email)->first();

//     if (!$user) {
//         return response()->json([
//             'error' => 'Email không tồn tại trong hệ thống'
//         ], 404);
//     }

//     try {
//         // Tạo token và URL đặt lại mật khẩu
//         $token = Password::createToken($user);
//         // $actionUrl = 'https://10truyen.com/reset-password/?reset_token=' . $token . '&email=' . urlencode($email);
//         $actionUrl = url('/reset-password/?reset_token=' . $token . '&email=' . urlencode($email));

//         // Gửi email tùy chỉnh với URL reset mật khẩu
//         Mail::to($email)->send(new ResetPasswordMail($actionUrl, $user->username));

//         return response()->json(['status' => 'Đường link đặt lại mật khẩu đã được gửi'], 200);

//     } catch (\Throwable $th) {
//         return response()->json([
//             'error' => 'Có lỗi xảy ra khi gửi đường link đặt lại mật khẩu',
//             'message' => $th->getMessage()
//         ], 500);
//     }
// }

    public function sendResetLinkEmail(Request $request)
    {
        $email = $request->input('email');

        if (empty($email)) {
            return $this->response([
                'message' => 'Email là bắt buộc.',
            ], 400);
        }
    
        // Kiểm tra xem email có đúng định dạng hay không
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response([
                'message' => 'Email không hợp lệ. Vui lòng nhập đúng định dạng email.',
            ], 400);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->response([
                'message' => 'Email không tồn tại trong hệ thống'
            ], 404);
        }

        try {
        $otp = mt_rand(1000, 9999);
        // Lưu vào bảng `password_reset_token`
        PasswordResetToken::updateOrCreate(
        ['email' => $email],
            [
                'token' => $otp,
                    'created_at' => Carbon::now(),
            ]
        );
        $emailContent = View::make('emails.password', ['username' => $user->username, 'user_email' => $user->email, 'otp' => $otp, 'support_email' => 'mailtrap@gocnhinannam.com'])->render();
        $client = new Client();
        $response = $client->post('https://send.api.mailtrap.io/api/send', [
            'headers' => [
                'Authorization' => 'Bearer 2c10d05f1cc1569ac8069800e8eb5d99',
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'from' => ['email' => 'mailtrap@gocnhinannam.com', 'name' => '10Truyen'],
                'to' => [['email' => $email]],
                'subject' => '10Truyen - Đặt lại mật khẩu',
                'text' => strip_tags($emailContent), // Nội dung email dạng text
                'html' => $emailContent,
                'category' => 'Integration Test',
            ],
        ]);

        return $this->response(['message' => 'Mã đặt lại mật khẩu đã được gửi'], 200);
    } catch (\Exception $e) {
        // return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        return $this->response([], 500);
    }

        // try {
        //     // Tạo mã OTP 4 chữ số
        //     $otp = mt_rand(1000, 9999);

        //     // Lưu vào bảng `password_reset_token`
        //     PasswordResetToken::updateOrCreate(
        //         ['email' => $email],
        //         [
        //             'token' => $otp,
        //             'created_at' => Carbon::now(),
        //         ]
        //     );

        //     // Gửi email với mã xác thực
        //     Mail::to($email)->send(new ResetPasswordMail($otp, $user->username));

        //     return $this->response(['message' => 'Mã đặt lại mật khẩu đã được gửi'], 200);

        // } catch (\Throwable $th) {
        //     return response()->json(['message' => $th->getMessage()], 500);
        // }
    }

    public function resetPasswordIndex(Request $request) {
        try {
        $token = $request->query('reset_token');
        $email = $request->query('email');
        $actionUrl = url('/api/v1/password/reset');
        return view('emails.reset-password', ['token' => $token, 'email' => $email, 'actionUrl' => $actionUrl]);
    } catch (\Throwable $th) {
        return $th->getMessage();
    }
    }

    // public function resetPassword(Request $request)
    // {
    //     try {
    //     $rules = [
    //         'token' => 'required',
    //         'email' => 'required|email',
    //         'password' => 'required|min:8|confirmed',
    //     ];
    
    //     // Thêm các thông báo tùy chỉnh
    //     $customMessages = [
    //         'token.required' => 'Token là bắt buộc.',
    //         'email.required' => 'Email là bắt buộc.',
    //         'email.email' => 'Email không hợp lệ.',
    //         'password.required' => 'Mật khẩu là bắt buộc.',
    //         'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
    //         'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
    //     ];
    
    //     $validator = FacadesValidator::make($request->all(), $rules, $customMessages);

    //     if ($validator->fails()) {
    //         return $this->response(["message" => $validator->errors()->first()], 400);
    //     }

    //     $user = User::where('email', $request->input('email'))->first();
    //     if (!$user) {
    //         return $this->response(['error' => 'Email không tồn tại trong hệ thống'], 404);
    //     }

    //     $status = Password::reset(
    //         $request->only('email', 'password', 'password_confirmation', 'token'),
    //         function ($user, $password) {
    //             $user->forceFill([
    //                 'password' => Hash::make($password),
    //             ])->save();
    //         }
    //     );

    //     if ($status === Password::PASSWORD_RESET) {
    //         return $this->response(['message' => 'Mật khẩu đã được đặt lại thành công'], 200);
    //     } else {
    //         // Xử lý các trường hợp thất bại
    //         $errorMessage = 'Đặt lại mật khẩu không thành công. Vui lòng thử lại sau.';

    //         if ($status === Password::INVALID_TOKEN) {
    //             $errorMessage = 'Token không hợp lệ. Vui lòng thử lại.';
    //         }
            
    //         throw new \Exception($errorMessage);
    //     }

    // } catch (\Throwable $th) {
    //     return $this->response(['message' => $th->getMessage()],500);
    // }
    // }

    public function verifyOtp(Request $request) {
        try {
            $tokenRecord = PasswordResetToken::where('email', $request->input('email'))
            ->where('token', $request->input('otp'))
            ->where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->first();

            if (!$tokenRecord) {
                return $this->response([
                    'message' => 'Mã OTP không hợp lệ hoặc đã hết hạn.',
                ], 400);
            }
        
            return $this->response(['message' => 'Mã OTP hợp lệ. Bạn có thể đặt lại mật khẩu.'], 200);
        
        } catch (\Throwable $th) {
            // return response()->json($th->getMessage(), 500);
            return $this->response([], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
        $user = User::where('email', $request->input('email'))->first();
        if (!$user) {
            return $this->response(['message' => 'Email không tồn tại trong hệ thống'], 404);
        }

        $user->forceFill([
            'password' => Hash::make($request->input('password')),
        ])->save();
    
        // Xóa mã OTP sau khi sử dụng
        PasswordResetToken::where('email', $request->input('email'))->delete(); // Đảm bảo mã OTP không được sử dụng lại

        return $this->response(['message' => 'Mật khẩu đã được đặt lại thành công'], 200);

    } catch (\Throwable $th) {
        return $this->response([], 500);
    }
    }
}
