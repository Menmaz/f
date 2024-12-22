<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <script>
        function isValidEmail(email) {
            // Kiểm tra định dạng email
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailPattern.test(email);
        }

        function toggleSendButton() {
            const emailField = document.getElementById("email").value;
            const sendButton = document.getElementById("send-code-button");

            // Nếu email hợp lệ, cho phép nhấn nút
            sendButton.disabled = !isValidEmail(emailField);
        }

        function sendCode() {
            const email = document.getElementById("email").value;
            const sendButton = document.getElementById("send-code-button");

            // Thêm hoạt ảnh loading và vô hiệu hóa nút
            sendButton.innerHTML = 'Đang gửi... <div class="spinner"></div>';
            sendButton.disabled = true;

            fetch("{{ url('/api/v1/password/forgot') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email }),
            })
            .then(response => {
                if (response.ok) {
                    alert("Mã xác thực đã được gửi đến email!");
                    document.getElementById("password-fields").style.display = "block"; // Hiển thị các trường mật khẩu
                } else {
                    response.json().then(data => alert(data.error));
                }
            })
            .catch(error => {
                alert("Có lỗi xảy ra khi gửi mã: " + error);
            })
            .finally(() => {
                // Khôi phục nút sau khi hoàn thành
                sendButton.innerHTML = 'Gửi mã';
                sendButton.disabled = false;
            });;
        }

        function checkToken() {
            const tokenField = document.getElementById("token").value;
            const confirmButton = document.getElementById("confirm-button");

            // Nếu token không trống, kích hoạt nút xác nhận
    confirmButton.disabled = (tokenField.trim() === '');
        }

        // Hàm để xử lý khi nhấn nút xác nhận (chỉ gán EventListener một lần)
function onConfirmButtonClick() {
    const emailField = document.getElementById("email").value;
    const tokenField = document.getElementById("token").value;
    const passwordField = document.getElementById("password").value;
    const passwordConfirmationField = document.getElementById("password_confirmation").value;

    fetch("{{ url('/api/v1/password/reset') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            token: tokenField,
            email: emailField,
            password: passwordField,
            password_confirmation: passwordConfirmationField,
        }),
    })
    .then(response => response.json())
    .then(data => {
            alert(data.message);
    })
    .catch(error => {
        alert('Lỗi, vui lòng kiểm tra lại');
    });
}

// Gán EventListener khi trang tải lần đầu (đảm bảo chỉ gán một lần)
window.onload = function() {
    const confirmButton = document.getElementById("confirm-button");

    // Đảm bảo EventListener chỉ được gán một lần
    confirmButton.removeEventListener('click', onConfirmButtonClick);
    confirmButton.addEventListener('click', onConfirmButtonClick);
};
    </script>
    <style>
        .form-group {
            margin-bottom: 10px;
        }

        .hidden {
            display: none;
        }

        .button {
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div style="max-width: 400px; margin: auto;">
        <h2>Quên mật khẩu</h2>

        <div class="form-group">
            <label>Email:</label>
            <input type="email" id="email" name="email" oninput="toggleSendButton()" required />
            <!-- Nút gửi mã (vô hiệu hóa nếu email không hợp lệ) -->
            <button id="send-code-button" type="button" class="button" onclick="sendCode()" disabled>Gửi mã</button>
        </div>

        <div class="form-group">
            <label>Mã xác thực:</label>
            <input type="text" id="token" name="token" oninput="checkToken()" />
        </div>

        <div id="password-fields" class="hidden">
            <div class="form-group">
                <label>Mật khẩu mới:</label>
                <input type="password" id="password" name="password" minlength="8" />
            </div>

            <div class="form-group">
                <label>Xác nhận mật khẩu:</label>
                <input type="password" id="password_confirmation" name="password_confirmation" minlength="8" />
            </div>
        </div>

        <div class="form-group">
            <!-- Nút xác nhận (vô hiệu hóa nếu token trống) -->
            <button id="confirm-button" type="button" class="button" disabled onclick="alert('Xác nhận đổi mật khẩu!')">Xác nhận</button>
        </div>
    </div>
</body>
</html>
