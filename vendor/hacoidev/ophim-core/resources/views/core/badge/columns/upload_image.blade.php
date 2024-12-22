
  <div class="form-group">
                <label for="imageUpload">Chọn Ảnh:</label>
                <input type="file" class="form-control-file" id="imageUpload" name="imageUpload" accept="image/*">
            </div>

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>

$('#imageUpload').click(function(event) {
    var badge_name = document.getElementById('badge_name').value;
				if (badge_name.trim() === '') {
                    event.preventDefault();
                    alert('Trước khi upload bạn cần tên huy hiệu trước !');
                }
			});


var imagesUrlString = "";
var imageUrlInput = document.getElementById('image_url');
document.getElementById('imageUpload').addEventListener('change', function() {
    var input = this;
    var file = input.files[0];
    var formData = new FormData();
    var badge_name = document.getElementById('badge_name').value;
    if (file && file.type.match('image.*')) {

        var reader = new FileReader();
        reader.onload = function(event) {
            var maxDataUrlLength = 100;
            var imageURL = event.target.result;
            console.log('Đường dẫn gốc của ảnh:', imageURL);
            imageUrlInput.value = imageURL;
        };
        reader.readAsDataURL(file);

    } else {
        alert('Chỉ được chọn ảnh');
    }
});


</script>



