
<div class="form-group">
                <label for="">Xem trước</label>
                @if(isset($badge))
               <div class="btn" id="previewBadge" style="background-color: {{ $badge->css_color }}">
                <span></span>
                <span style='color:white' id="badgeName">{{$badge->name}}</span>
                <span></span>
                </div>
                @else
                <div class="btn" id="previewBadge" style="background-color: transparent;">
                <span></span>
                <span style='color:white' id="badgeName"></span>
                <span></span>
                </div>
                @endif
            </div>


    <!-- Bootstrap JS and dependencies (optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
    var previewBadge = document.getElementById('previewBadge');
    var badgeName = document.getElementById('badgeName');

    document.getElementById('cssColorText').addEventListener('input', function(e) {
        var badgeNameValue = document.getElementById('badgeNameInput').value;
        if(badgeNameValue.trim() === ''){
            e.preventDefault();
            alert('Phải nhập tên huy hiệu trước')
        } else {
            var color = this.value;
            previewBadge.style.backgroundColor = color;
            previewBadge.style.borderColor = 'rgba(0, 0, 0, 0.1)';
            badgeName.textContent = badgeNameValue;
        }
        
    });
</script>




