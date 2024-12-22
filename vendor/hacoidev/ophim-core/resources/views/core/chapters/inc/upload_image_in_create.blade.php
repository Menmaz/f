
  <div class="form-group">
                <label for="imageUpload">Chọn Ảnh:</label>
                <input type="file" class="form-control-file" id="imageUpload" name="imageUpload" multiple accept="image/*">
            </div>
        

    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>

$('#imageUpload').click(function(event) {
				var chapter = $('#chapterNumber').val();
				if (chapter.trim() === '' || !/^\d+(\.\d+)?$/.test(chapter) || parseFloat(chapter) <= 0) {
                    event.preventDefault();
                    alert('Trước khi upload bạn cần điền số chương trước. VD 1 hoặc 1.5 hoặc 2');
                }
			});

            
var imagesUrlString = "";
document.getElementById('imageUpload').addEventListener('change', function() {
    var input = this;
    var formData = new FormData();
    var chapter_number = document.getElementById('chapterNumber').value;
    var imageUrlsElement = document.getElementById('imageUrls');
    var currentValue = imageUrlsElement.value;

    for (var i = 0; i < input.files.length; i++) {
        var file = input.files[i];
            if (file.type.match('image.*')) {
            formData.append('images', file);
            formData.append('chapter_number', chapter_number);
            
            $.ajax({
                url: 'upload-images',
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    const error = response.error;
                    if(error){
                        alert(error)
                        return;
                    }
                    var imageUrl = response.image_url;
                    if(currentValue.trim() == ''){
                        currentValue = currentValue + imageUrl;
                    } else {
                        currentValue = currentValue + "\n" + imageUrl;
                    }
                    imageUrlsElement.value = currentValue;
                    // imageUrlsElement.value = imagesUrlString;
                },
            });

        }
    }

});


</script>



