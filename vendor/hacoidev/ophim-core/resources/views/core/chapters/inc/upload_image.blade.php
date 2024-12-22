
<div class="form-group">
    <label for="imageUpload">Chọn Ảnh:</label>
    <input type="file" class="form-control-file" id="imageUpload" name="imageUpload" multiple accept="image/*">
</div>
<div id="uploading" class="alert alert-primary text-center" style="display: none;">
    Vui lòng đợi, đang tải ảnh lên...
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


document.getElementById('imageUpload').addEventListener('change', function() {
    var input = this;
    var formData = new FormData();
    var chapter_number = document.getElementById('chapterNumber').value;
    var imageUrlsElement = document.getElementById('imageUrls');
    var currentValue = imageUrlsElement.value;

    // Add chapter number to form data
    formData.append('chapter_number', chapter_number);

    // Append all selected files to formData
    for (var i = 0; i < input.files.length; i++) {
        var file = input.files[i];
        if (file.type.match('image.*')) {
            formData.append('images[]', file); // Note: 'images[]' for multiple files
        }
    }
    
    // Show the "Vui lòng đợi" message
    document.getElementById('uploading').style.display = 'block';

    // Send all files in a single request
    $.ajax({
        url: "{{ backpack_url('chapters/'.$manga->slug.'/upload-images') }}",
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        success: function(response) {
            const error = response.error;
            if(error){
                alert(error);
                return;
            }
            
            console.log(response.image_urls);

            var newImageUrls = response.image_urls || [];
            var currentUrls = new Set(currentValue.split('\n').map(url => url.trim()).filter(url => url.trim() !== ''));
            var uniqueNewUrls = newImageUrls.filter(url => !currentUrls.has(url));
            
            // Combine and sort URLs
            var allImageUrls = Array.from(currentUrls).concat(uniqueNewUrls);
            var sortedImageUrls = sortImageUrls(allImageUrls.join('\n'), '');
            imageUrlsElement.value = sortedImageUrls.join('\n');
            alert('Đã tải xong ' + sortedImageUrls.length + " ảnh !");
            
            // Hide the "Vui lòng đợi" message
            document.getElementById('uploading').style.display = 'none';
        },
        error: function() {
            // Hide the "Vui lòng đợi" message in case of error
            document.getElementById('uploading').style.display = 'none';
            alert('Đã xảy ra lỗi trong quá trình tải lên.');
        }
    });
});

function sortImageUrls(currentValue, newFileName) {
var urls = currentValue.split('\n').filter(function(url) {
return url.trim() !== '';
});

// Ensure newFileName is not added if already present
if (newFileName && !urls.includes(newFileName)) {
        urls.push(newFileName); // Add new file name
}

urls.sort(function(a, b) {
    var numA = extractNumberFromFileName(a);
    var numB = extractNumberFromFileName(b);
    return numA - numB;
});

return urls;
}

function extractNumberFromFileName(filePath) {
    var fileName = filePath.split('/').pop();

    var match = fileName.match(/(\d+)(?:\.(jpg|png|webp))$/i);
    if (match && match[1]) {
        return parseFloat(match[1]); 
    }
    
    return 0; 
}


</script>



