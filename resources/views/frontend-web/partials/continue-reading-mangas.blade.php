<div class="original card-lg reading"> @foreach(session('reading_mangas') as $readingManga) <div class="unit">
    <div class="inner">
      <button class="reading-remove" data-id="{{ $readingManga['session_id'] }}">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <a href="{{ route('manga.detail', ['slug' => $readingManga['slug']]) }}" class="poster">
        <div>
          <img src="{{ $readingManga['cover'] }}">
        </div>
      </a>
      <div class="info">
        <div>
          <span class="type">Manga</span>
        </div>
        <a href="{{ route('manga.detail', ['slug' => $readingManga['slug']]) }}">{{ $readingManga['title'] }}</a>
        <p> Chapter {{ $readingManga['current_chapter'] }} / {{ $readingManga['total_chapters'] }}
        </p>
      </div>
    </div>
  </div> @endforeach </div>
<script>
  const removeButtons = document.querySelectorAll('.reading-remove');
  removeButtons.forEach(button => {
    button.addEventListener('click', function(event) {
      event.preventDefault();
      const sessionId = this.getAttribute('data-id');
      fetch("{{ url('user/remove-reading-manga') }}/" + sessionId, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json'
        },
      }).then(response => {
        if (!response.ok) {
          toastr.error("Có lỗi xảy ra")
        }
        return response.json();
      }).then(data => {
        const mangaElement = this.closest('.unit');
        mangaElement.remove();
        toastr.success("Xóa thành công")
      }).catch(error => {
        toastr.error("Có lỗi xảy ra")
      });
    });
  });
</script>