<?php

namespace App\Http\Controllers\APIs;

use App\Helpers\ImageHelper;
use App\Http\Controllers\APIs\Contracts\ApiBase;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\RefreshToken;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator as FacadesValidator;
use Ophim\Core\Controllers\Admin\ImageStorageManager;
use Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends ApiBase
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    //new version
    public function login(Request $request){
        try {
            //code...
            $rules = [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ];
    
            // Thông điệp tùy chỉnh
            $customMessages = [
                'email.required' => 'Email là bắt buộc.',
                'email.email' => 'Vui lòng nhập địa chỉ email hợp lệ.',
                'password.required' => 'Mật khẩu là bắt buộc.',
                'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            ];

            $validator = FacadesValidator::make($request->all(), $rules, $customMessages);

        if ($validator->fails()) {
            // return response()->json($validator->errors(), 422);
            return $this->response(['message' => 'Dữ liệu đầu vào có vấn đề'], 401);
        }

        if (! $token = auth()->attempt($validator->validated())) {
            return $this->response(['message' => 'Email hoặc mật khẩu không đúng'], 401);
        }

        return $this->createNewToken($token);
    } catch (\Throwable $th) {
        return $this->response(['message' => $th->getMessage()], 500);
    }
    }


    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    //new version
    public function register(Request $request) {
        $rules = [
            'username' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
        ];
    
        // Thêm các thông báo tùy chỉnh
        $customMessages = [
            'username.required' => 'Tên người dùng là bắt buộc.',
            'username.between' => 'Tên người dùng phải có độ dài từ 2 đến 100 ký tự.',
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email phải là địa chỉ email hợp lệ.',
            'email.unique' => 'Email đã tồn tại, vui lòng sử dụng email khác.',
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.confirmed' => 'Mật khẩu không khớp với xác nhận mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ];
    
        // Xác thực yêu cầu với các thông báo tùy chỉnh
        $validator = FacadesValidator::make($request->all(), $rules, $customMessages);
    
        // Kiểm tra lỗi xác thực
        if ($validator->fails()) {
            return $this->response(["message" => $validator->errors()->first()], 400);
        }
    
        // Thêm người dùng mới vào cơ sở dữ liệu
        try {
            $user = new User();
            $user->username = $request->username;
            $user->email = $request->email;
            $user->password = bcrypt(trim($request->password));
            $user->save();
        } catch (\Exception $e) {
            return $this->response(['message' => $e->getMessage()], 500);
        }
    
        // Trả về phản hồi thành công
        // return response()->json([
        //     'message' => 'Người dùng đã đăng ký thành công.',
        //     'user' => $user
        // ], 200);
        return $this->response(['message' => 'Người dùng đã đăng ký thành công.'], 200);
    }


    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();
        return $this->response(['message' => 'User successfully signed out'], 200);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    // public function refresh() {
    //     return $this->createNewToken(auth()->refresh());
    // }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        // return response()->json(auth()->user());
        try {
            return response()->json(['data' => new UserResource(auth()->user())]);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
        }
    }

    public function updateUserProfile(Request $request) {
        try {
            $user = auth()->user();
        if ($request->filled('username')) {
            $updates['username'] = $request->input('username');
        }

        if ($request->filled('email')) {
            $newEmail = $request->input('email');
            
            if ($newEmail !== $user->email) {
                $existingUser = User::where('email', $newEmail)->first();

                if ($existingUser) {
                    return $this->response([
                        'message' => "Email đã tồn tại.",
                    ], 400);
                }
                
                $updates['email'] = $newEmail;
            }
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            if ($file->isValid()) {
                // Kiểm tra MIME type để đảm bảo chỉ có tệp hình ảnh
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($file->getMimeType(), $allowedMimes)) {
                    return $this->response([
                        'message' => "Chỉ chấp nhận các định dạng ảnh: JPEG, PNG, hoặc WebP.",
                    ], 400);
                }

                // Kiểm tra kích thước tệp (ví dụ, không lớn hơn 2MB)
                if ($file->getSize() > 2 * 1024 * 1024) {
                    return $this->response([
                        'message' => "Kích thước ảnh quá lớn, vui lòng chọn ảnh nhỏ hơn 5MB.",
                    ], 400);
                }
                $imageData = file_get_contents($file->path());

                $userAvatar = ImageHelper::uploadedUserAvatar($user->id, $user->username, $imageData);
                $updates['avatar'] = $userAvatar;
            } else {
                return $this->response([
                    'message' => "File ảnh không hợp lệ.",
                ], 400);
            }
        }

        if ($request->filled('new_password')) {
            $newPassword = $request->input('new_password');
            $user->update(['password' => Hash::make($newPassword)]);
        }


        if (!empty($updates)) {
            $user->update($updates); 
        }
    
        return $this->response([
            'message' => 'Cập nhật thông tin người dùng thành công!',
            'data' => new UserResource($user),
        ], 200);
        } catch (\Throwable $th) {
            return $this->response(['message' => $th->getMessage()], 500);
            // return $th->getMessage();
        }
    }

    // public function changePassWord(Request $request) {
    //     try {
    //         $validator = FacadesValidator::make($request->all(), [
    //             'old_password' => 'required|string|min:6',
    //             'new_password' => 'required|string|confirmed|min:6',
    //         ]);
    
    //         if($validator->fails()){
    //             return response()->json($validator->errors()->toJson(), 400);
    //         }
    //         $userId = auth()->user()->id;
    
    //         $user = User::where('id', $userId)->update(
    //                     ['password' => bcrypt($request->new_password)]
    //                 );
    
    //         return response()->json([
    //             'message' => 'User successfully changed password',
    //             'user' => $user,
    //         ], 201);
        
    //     } catch (\Exception $e) {
        
    //         return $e->getMessage();
    //     }

    // }


    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        // Tạo refresh token với thời hạn 15 ngày
            $refreshToken = Str::random(40);
            $expiresAt = Carbon::now()->addDays(15); // 15 ngày kể từ bây giờ

            RefreshToken::updateOrCreate(
                ['user_id' => auth()->user()->id], 
                [
                    'token' => $refreshToken,
                    'expires_at' => $expiresAt,
                ]
            );

            $refreshExpiresInSeconds = $expiresAt->diffInSeconds(Carbon::now());

        return response()->json([
            'access_token' => $token,
            'expires_in' => $refreshExpiresInSeconds,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
            'user' => new UserResource(auth()->user())
            // 'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

}
