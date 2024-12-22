@extends(backpack_view('layouts.plain'))

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-5">
            
            <div class="card card-md text-white" style="background-color: #221E26;">
                <div class="card-body pt-0">
                    <h3 class="h3 text-center my-4">{{ trans('backpack::base.login') }}</h3>
<form method="POST" action="{{ route('backpack.auth.login') }}" autocomplete="off" novalidate="">
    {!! csrf_field() !!}
    @csrf
    <div class="mb-3">
        <label class="form-label" for="{{ $username }}">{{ config('backpack.base.authentication_column_name') }}</label>
        <input autofocus="" tabindex="1" type="email" class="form-control{{ $errors->has($username) ? ' is-invalid' : '' }}" name="{{ $username }}" value="{{ old($username) }}" id="{{ $username }}">
        @if ($errors->has($username))
            <span class="invalid-feedback">
                <strong>{{ $errors->first($username) }}</strong>
            </span>
        @endif
            </div>
    <div class="mb-2">
        <label class="form-label" for="password">
        {{ trans('backpack::base.password') }}
        </label>
        <input tabindex="2" type="password" name="password" id="password" class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}">
            </div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-check mb-0">
            <input name="remember" tabindex="3" type="checkbox" class="form-check-input">
            <span class="form-check-label">Ghi nhớ</span>
        </label>
                    <!-- <div class="form-label-description">
                <a tabindex="4" href="https://demo.backpackforlaravel.com/admin/password/reset">Quên mật khẩu?</a>
            </div> -->
            </div>
    <div class="form-footer">
        <button tabindex="5" type="submit" class="btn btn-primary w-100">Đăng nhập</button>
    </div>
</form>                </div>
            </div>

            
            @if (backpack_users_have_email() && config('backpack.base.setup_password_recovery_routes', true))
                <div class="text-center"><a href="{{ route('backpack.auth.password.reset') }}">{{ trans('backpack::base.forgot_your_password') }}</a></div>
            @endif
            @if (config('backpack.base.registration_open'))
                <div class="text-center"><a href="{{ route('backpack.auth.register') }}">{{ trans('backpack::base.register') }}</a></div>
            @endif
        </div>
    </div>
@endsection
