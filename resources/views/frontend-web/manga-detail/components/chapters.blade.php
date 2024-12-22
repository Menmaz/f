<div class="list-body">
        <ul class="scroll-sm" id="chapterList"> 
        @foreach($manga['chapters'] as $chapter)
        <li class="item" data-number="{{ $chapter['chapter_number'] }}">
            <a href="{{ route('manga.chapter', ['slug' => $manga['slug'], 'chapter_number' => $chapter['chapter_number'] ]) }}" title="Chapter {{ $chapter['chapter_number'] }}">
                <span>Chapter {{ $chapter['chapter_number'] }}</span>
                <span>{{ \Carbon\Carbon::parse($chapter['created_at'])->diffForHumans() }}</span>
            </a>
        </li>
        @endforeach
        </ul>
</div>