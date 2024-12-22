<aside class="sidebar">
    <ul class="user-nav">
        <li>
            <a class="{{ request()->routeIs('user.profile') ? 'active' : '' }}" href="{{ route('user.profile') }}">
                <i class="fa-solid fa-user"></i>
                <span>Hồ sơ</span>
            </a>
        </li>
        <li>
            <a class="{{ request()->routeIs('user.reading') ? 'active' : '' }}" href="{{ route('user.reading') }}">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Lịch sử đọc</span>
            </a>
        </li>
        <li>
            <a class="{{ request()->routeIs('user.bookmark') || request()->routeIs('user.filter-bookmark') ? 'active' : '' }}" href="{{ route('user.bookmark') }}">
                <i class="fa-solid fa-bookmark"></i>
                <span>Truyện đã lưu</span>
            </a>
        </li>
        <li>
            <a class="{{ request()->routeIs('user.notification') ? 'active' : '' }}" href="{{ route('user.notification') }}">
                <i class="fa-solid fa-bell"></i>
                <span>Thông báo</span>
            </a>
        </li>
        <li>
            <a class="{{ request()->routeIs('user.settings') ? 'active' : '' }}" href="{{ route('user.settings') }}">
                <i class="fa-solid fa-gear"></i>
                <span>Cài đặt</span>
            </a>
        </li>
    </ul>
</aside>
