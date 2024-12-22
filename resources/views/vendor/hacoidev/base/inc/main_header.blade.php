<header class="{{ config('backpack.base.header_class') }}">
  <!-- Logo -->
  <button class="navbar-toggler sidebar-toggler d-lg-none mr-auto ml-3" type="button" data-toggle="sidebar-show" aria-label="{{ trans('backpack::base.toggle_navigation')}}">
    <span class="navbar-toggler-icon"></span>
  </button>
  <a class="navbar-brand" href="{{ backpack_url('dashboard') }}" title="{{ config('backpack.base.project_name') }}">
    10 CMS
    <h6><span class="badge badge-danger">v{{ config('custom.cms_version') }}</span></h6>
  </a>
  <button class="navbar-toggler sidebar-toggler d-md-down-none" type="button" data-toggle="sidebar-lg-show" aria-label="{{ trans('backpack::base.toggle_navigation')}}">
    <span class="navbar-toggler-icon"></span>
  </button>

  @include(backpack_view('inc.menu'))
</header>

<style>
  .app-header.bg-light .navbar-brand {
    pointer-events: none;
    opacity: 1;
  }
</style>
