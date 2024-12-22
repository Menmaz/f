<!DOCTYPE html>
<html>
<head>
    <title>Xác thực đăng nhập</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Xác thực đăng nhập</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('verify.code.post') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="code">Nhập mã:</label>
                                <input type="text" id="code" name="code" class="form-control" required>
                                @if ($errors->has('code'))
                                    <div class="alert alert-danger mt-2">{{ $errors->first('code') }}</div>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-primary">Tiếp tục</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
