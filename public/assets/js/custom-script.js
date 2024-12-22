// document.addEventListener('DOMContentLoaded', function() {
//     var images = [];
//     var test = "";
//     document.getElementById('imageUpload').addEventListener('change', function() {
//         var input = this;
//         // Lặp qua từng tệp được chọn
//         for (var i = 0; i < input.files.length; i++) {
//             var file = input.files[i];
//             // Kiểm tra định dạng của tệp
//             if (file.type === 'image/jpeg' || file.type === 'image/png') {
//                 var url = URL.createObjectURL(file);
//                 test += url + "\n";
//                 images.push(url);
//             }
//         }
//         // Gán mảng images cho trường ẩn content
//         document.getElementById('imageUrlsToShow').value = test;
//         document.getElementById('imageUrls').value = JSON.stringify(images);
//     });
// });


document.addEventListener('DOMContentLoaded', function() {
    var images = [];

    document.getElementById('imageUpload').addEventListener('change', function() {
        var input = this;
        var formData = new FormData();

        // Thêm các file vào FormData
        for (var i = 0; i < input.files.length; i++) {
            var file = input.files[i];
            if (file.type === 'image/jpeg' || file.type === 'image/png') {
                formData.append('images[]', file);
            }
        }

        // Gửi FormData thông qua Ajax
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'your-api-url', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Lấy các đường dẫn ảnh từ phản hồi API
                var response = JSON.parse(xhr.responseText);
                var imageUrls = response.imageUrls;

                // Gán các đường dẫn ảnh vào textarea
                var textarea = document.getElementById('imageUrlsToShow');
                textarea.value = imageUrls.join('\n');

                // Lưu các đường dẫn ảnh vào mảng images
                images = imageUrls;
            }
        };
        xhr.send(formData);
    });
});

