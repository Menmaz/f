<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <title>Hồ sơ</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta charset="utf-8">
    <meta name="robots" content="index, follow">
    <meta name="revisit-after" content="1 days">
    <base href="">
    <meta property="og:type" content="website">
    <meta property="og:title" content="">
    <meta property="og:description" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1 ">
    <link rel="icon" type="image/png" href="{{ asset('frontend-web/images/favicon.png') }}">
    
    
  </head>
  <body>
    <span class="bg"></span>
    <div class="wrapper"> @include('frontend-web.home.components.header')
      
    <main class="user-panel">
        <div class="container">
          <div class="main-inner"> @include('frontend-web.user.components.sidebar') 
            <aside class="content">
              <section class="max-sm">
                <div class="head">
                  <h2>Hồ sơ</h2>
                </div>

                <form id="update-profile-form" autocomplete="off" class="ajax-update-profile" action="{{ route('user.update-profile') }}" method="post" enctype="multipart/form-data">
                  @csrf
                  <!-- Form content -->
                  <div class="form-group">
                    <input type="text" class="form-control" name="username" value="{{ Auth::user()->username }}" />
                  </div>
                  <div class="form-group">
                    <input type="email" class="form-control" name="email" value="{{ Auth::user()->email }}" />
                  </div>
                  <button class="btn p-0" type="button" data-toggle="collapse" data-target="#changepass" aria-controls="changepass">
                    <i class="fa-solid fa-key fa-sm"></i> Đổi mật khẩu 
                  </button>
                  <div class="collapse" id="changepass">
                    <div class="form-group mt-3">
                      <input type="password" class="form-control" name="password" placeholder="Mật khẩu mới" />
                    </div>
                    <div class="form-group mb-0">
                      <input type="password" class="form-control" name="password_confirmation" placeholder="Mật khẩu cũ" />
                    </div>
                  </div>
                  {{-- <center>
                    <div class="form-group">
                      <div id="avatar-preview" class="avatar-preview">
                        <img style="border-radius: 50%; object-fit: cover;box-shadow: 0 5px 20px 0 #3c8bc6" width="140" height="140" src="{{ Auth::user()->avatar ?? asset('frontend-web/images/user-default-avatar.jpg') }}" alt="Avatar" id="current-avatar" class="avatar-img" onerror="this.onerror=null;this.src='{{ asset('frontend-web/images/user-default-avatar.jpg') }}';">
                      </div>
                    </div> 
                    <div class="form-group">
                      <input type="file" name="avatar" id="avatar" accept="image/*" style="display: none;">
                      <button type="button" id="choose-avatar-btn" class="btn btn-sm btn-primary mt-2">Chọn ảnh đại diện khác</button>
                      <p id="file-name" class="ml-2"></p> 
                    </div>
                  </center> --}}
                  <div>
                    <button type="submit" class="submit btn w-100 mt-3 btn-lg btn-primary"> Lưu thay đổi <i class="fa-solid fa-check"></i>
                    </button>
                  </div>
                </form>

              </section>
            </aside>

            <aside class="content">
              <center>
              <section class="max-sm">
                <div class="head">
                  <h2>Huy hiệu</h2>
                </div>
                <form id="" autocomplete="off" class="form-horizontal" action="" method="post">
    @csrf
    <div class="row">
        <!-- Cột đầu tiên: Avatar Preview -->
        <div class="col-md-4 text-center">
            <div class="form-group">
                      <div id="avatar-preview" class="avatar-preview">
                        <img style="border-radius: 50%; object-fit: cover;box-shadow: 0 5px 20px 0 #3c8bc6" width="120" height="120" src="{{ Auth::user()->avatar ?? asset('frontend-web/images/user-default-avatar.jpg') }}" alt="Avatar" id="current-avatar" class="avatar-img" onerror="this.onerror=null;this.src='{{ asset('frontend-web/images/user-default-avatar.jpg') }}';">
                      </div>
                    </div> 
                    <div class="form-group">
                      <input type="file" name="avatar" id="avatar" accept="image/*" style="display: none;">
                      <button type="button" id="choose-avatar-btn" class="btn btn-sm btn-primary mt-2">Thay avatar</button>
                      <p id="file-name" class="ml-2"></p> 
                    </div>
        </div>

        <!-- Cột thứ hai: Badge và Progress -->
        <div class="col-md-8">
            <div class="form-group">
                @if(is_array($badge) && !empty($badge))
                    @foreach($badge as $b)
                        <div class="badge mb-4">
                            <div class="avatar-preview">
                                <img height="90" src="{{ $b['image'] ?? '' }}" 
                                     onerror="this.onerror=null;this.src='{{ asset('frontend-web/images/user-default-avatar.jpg') }}';">
                            </div>
                            <div style="background: {{ $b['cssColor'] ?? '#000' }}; display: inline-block; text-align: center; margin: 0 auto; border-radius: 20px; font-size: 13px; color: white; padding: 8px;">
                                {{ $b['name'] ?? 'N/A' }}
                            </div>
                            <div class="mt-3 d-flex align-items-center">
                                <div class="progress" style="height: 30px; position: relative; background: #182335; border: 1px solid #1e2c43; box-shadow: 0 5px 20px #3c8bc6; flex-grow: 1;">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" 
                                         style="width: {{ $b['progress'] ?? 0 }}%;" 
                                         aria-valuenow="{{ $b['progress'] ?? 0 }}" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <span style="position: absolute; right: 10px; color: white; font-size: 15px;">
                                            {{ round($b['progress'] ?? 0, 1) }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</form>
              </section>
            </center> 
            </aside>

          </div>
        </div>
    </div>
    </main>
    
      @include('frontend-web.home.components.footer')

    </div>
  </body>
</html>

<script>
  $(document).ready(function() {
    $('.ajax-update-profile').on('submit', function(e) {
                      e.preventDefault();
                
                      var form = $(this)[0]; 
                      var formData = new FormData(form);
                
                      $.ajax({
                        type: "POST",
                        url: form.action,
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                          if (response.status === 'success') {
                            toastr.success(response.message);
                          } else {
                            toastr.error(response.message);
                          }
                        },
                        error: function() {
                          toastr.error("Lỗi hệ thống !");
                        }
                      });
                    });

    var $avatarInput = $('#avatar');
    var $chooseAvatarBtn = $('#choose-avatar-btn');
    var $fileName = $('#file-name'); // Optional: span to display the file name
    var $currentAvatar = $('#current-avatar'); // The image element for preview

    $chooseAvatarBtn.click(function() {
        $avatarInput.click();
    });

    // $avatarInput.change(function() {
    //     var file = this.files[0];
    //     if (file) {
    //         var reader = new FileReader();
    //         reader.onload = function(e) {
    //             $currentAvatar.attr('src', e.target.result);
    //         }
    //         reader.readAsDataURL(file);

    //         var fileName = $avatarInput.val().split('\\').pop();
    //         $fileName.text(fileName); // Optional: update the file name display
    //     }
    // });
    $avatarInput.change(function() {
        var file = this.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $currentAvatar.attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
            
            var formData = new FormData();
            formData.append('avatar', file);
            
            $('#choose-avatar-btn').addClass('disabled').text('Vui lòng đợi...');
    
            fetch("{{ route('user.update-avatar') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    toastr.success('Cập nhật avatar thành công');
                } else {
                    toastr.error('Lỗi rồi: ' + data.message);
                }
                
                $('#choose-avatar-btn').removeClass('disabled').text('Thay avatar');
            })
            .catch(error => {
                console.error('Error updating avatar:', error);
                $('#choose-avatar-btn').removeClass('disabled').text('Thay avatar');
            });
        }
    });
  });
</script>

