<!-- This file is used to store sidebar items, starting with Backpack\Base 0.9.0 -->
@if (backpack_user()->hasRole('Admin'))
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i>
        {{ trans('backpack::base.dashboard') }}</a></li>
@endif

<li class="nav-title">Truyện</li>
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i
            class="nav-icon la la-list"></i>Quản lý truyện</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('manga') }}'>
                <i class='nav-icon la la-play-circle'></i>
                <span>Truyện</span></a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('chapter') }}'>
                <i class='nav-icon la la-folder'></i>
                <span>Chapter</span></a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('chapter_report') }}'>
                <i class='nav-icon la la-info-circle'></i>
                <span>Truyện lỗi</span></a></li>
    </ul>
</li>


@if (backpack_user()->hasRole('Admin'))
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i
            class="nav-icon la la-list"></i> Phân loại</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('category') }}'><i class='nav-icon la la-at'></i>
                Thể loại</a></li>
    </ul>
</li>


<li class="nav-title">Bình luận</li>
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i
            class="nav-icon la la-list"></i>Quản lý bình luận</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('comment') }}'>
                <i class='nav-icon la la-comment'></i>
                <span>Bình luận</span></a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('comment_report') }}'>
                <i class='nav-icon la la-comment-slash'></i>
                <span>Bình luận spam</span></a></li>
    </ul>
</li>

<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i
            class="nav-icon la la-list"></i>Quản lý đánh giá</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('star_rating') }}'>
                <i class='nav-icon la la-star'></i>
                <span>Đánh giá sao</span></a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('icon_rating') }}'>
                <i class='nav-icon la la-grin-tongue'></i>
                <span>Đánh giá icon</span></a></li>
    </ul>
</li>

<li class="nav-title">Tuỳ chỉnh</li>    
<li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#"><i
            class="nav-icon la la-cog"></i> Cài đặt</a>
    <ul class="nav-dropdown-items">
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting/group/generals/edit') }}'><i
                    class='nav-icon la la-wrench'></i>
                <span>General</span></a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting/group/metas/edit') }}'><i
                    class='nav-icon la la-chevron-circle-up'></i>
                <span>SEO</span></a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting/group/image_services/edit') }}'><i
                class='nav-icon la la-image'></i>
            <span>Dịch vụ ảnh</span></a></li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting/group/notifications/edit') }}'><i class='nav-icon la la-bell'></i>
                Thông báo</a>
        </li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('ads') }}'><i class='nav-icon la la-ad'></i>
                Quảng cáo</a>
        </li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('terms') }}'><i class='nav-icon la la-file-contract'></i>
            Terms</a>
        </li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting/group/community/edit') }}'><i class='nav-icon la la-users'></i>
            Cộng đồng</a>
        </li>
        <li class='nav-item'><a class='nav-link' href='{{ backpack_url('setting/group/others/edit') }}'><i
                    class='nav-icon la la-slack'></i>
                <span>Khác</span></a></li>
    </ul>
</li>
<li class='nav-item'>
    <a class='nav-link' href='{{ backpack_url('sitemap/create') }}'><i class='nav-icon la la-map'></i>
        Sitemap</a>
</li>

<li class="nav-title">Mở rộng</li>
        
@foreach (config('plugins', []) as $plugin)
    @if (count($plugin['entries'] ?? []) > 1)
        <li class="nav-item nav-dropdown"><a class="nav-link nav-dropdown-toggle" href="#">
                <i class="nav-icon {{ $plugin['icon'] ?? '' }}"></i> {{ $plugin['name'] ?? '' }}</a>
            <ul class="nav-dropdown-items">
                @foreach ($plugin['entries'] ?? [] as $entry)
                    <li class='nav-item'><a class='nav-link' href='{{ $entry['url'] ?? '#' }}'>
                            <i class='nav-icon {{ $entry['icon'] ?? '' }}'></i>
                            <span>{{ $entry['name'] ?? '' }}</span></a></li>
                @endforeach
            </ul>
        </li>
    @else
        @foreach ($plugin['entries'] ?? [] as $entry)
            <li class='nav-item'><a class='nav-link' href='{{ $entry['url'] ?? '#' }}'>
                    <i class='nav-icon {{ $entry['icon'] ?? '' }}'></i>
                    <span>{{ $plugin['name'] ?? '' }}</span></a></li>
        @endforeach
    @endif
@endforeach

    <li class="nav-title">{{ trans('backpack::base.administration') }}</li>
    <li class="nav-item nav-dropdown">
        <a class="nav-link nav-dropdown-toggle" href="#"><i class="nav-icon la la-users"></i>
        Quản lý xác thực</a>
        <ul class="nav-dropdown-items">
            <li class="nav-item"><a class="nav-link" href="{{ backpack_url('user') }}"><i
                        class="nav-icon la la-user"></i>
                    <span>Quản lý tài khoản</span></a></li>
            <li class="nav-item"><a class="nav-link" href="{{ backpack_url('role') }}"><i
                        class="nav-icon la la-id-badge"></i> <span>Quản lý vai trò</span></a></li>
            <li class="nav-item"><a class="nav-link" href="{{ backpack_url('permission') }}"><i
                        class="nav-icon la la-key"></i> <span>Quản lý quyền</span></a></li>
        </ul>
    </li>
    <li class="nav-item"><a class="nav-link" href="{{ backpack_url('badge') }}"><i
                        class="nav-icon la la-bolt"></i> <span>Quản lý huy hiệu</span></a></li>
@endif
