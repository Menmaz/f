<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ophim\Core\Models\Contact;
use Ophim\Core\Models\Setting;

class ContactController extends Controller
{
    public function index()
    {
        $data = [
            'seoData' => $this->getSeoData()
        ];
        return view('frontend-web.contact.index', $data);
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
        $seoData['url_meta'] = config('custom.frontend_url');
        $seoData['site_script'] = $settings->get('site_scripts_google_analytics')->value;

        return $seoData;
    }

    public function sendContact(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'g-recaptcha-response' => 'required'
        ], [
            'required' => ':attribute là bắt buộc.',
            'email' => ':attribute phải là một địa chỉ email hợp lệ.',
            'max' => [
                'string' => ':attribute không được vượt quá :max ký tự.',
            ],
            'g-recaptcha-response.required' => 'Vui lòng xác thực reCAPTCHA.'
        ], [
            'email' => 'Email',
            'subject' => 'Tiêu đề',
            'message' => 'Nội dung',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first()
            ]);
        }

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

        Contact::create($request->all());

        return response()->json([
            'status' => 'success',
            'message' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.'
        ]);
    }
    
    public function terms()
    {
        $data = [
            'seoData' => $this->getSeoData(),
            'terms' => Setting::where('key', 'terms')->value('value')
        ];
        return view('frontend-web.contact.terms', $data);
    }


}
