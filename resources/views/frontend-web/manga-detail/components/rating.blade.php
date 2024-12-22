@php
    $rating = $manga->star_ratings_avg_rating ? round($manga->star_ratings_avg_rating) : 5;
    if (auth()->check()) {
        $userRating = $manga->starRatings->where('user_id', auth()->id())->first();
        $rating = $userRating ? $userRating->rating : $rating;
    }
@endphp

<div class="rating-box" itemprop="aggregateRating" itemscope="" itemtype="https://schema.org/AggregateRating" data-id="78" data-score="{{ $averageStarRating ?? 0}}">
        <div class="score">
         <div>
          <span class="live-score" itemprop="ratingValue">{{ number_format($manga->star_ratings_avg_rating, 2) }}</span> / <span itemprop="bestRating">5</span>
         </div>
         <span class="live-label">{{ count($manga->starRatings) > 0 ? count($manga->starRatings) : 1 }} đánh giá</span>
        </div>
        <div class="stars" data-rating="{{ $rating }}">
            @for ($i = 1; $i <= 5; $i++)
                <span data-value="{{ $i }}">
                    <i class="fa-regular fa-star @if($i <= $rating) fa-solid @endif"></i>
                </span>
            @endfor
        </div>
       </div>


<script>
  const starContainers = document.querySelectorAll('.stars');
  const starDescriptions = ['Dở tệ', 'Không hay', 'Tạm được', 'Hay', 'Xuất sắc'];
  starContainers.forEach(starContainer => {
   const rating = parseFloat(starContainer.getAttribute('data-rating'));
   const stars = starContainer.querySelectorAll('span');
   const liveScore = starContainer.previousElementSibling.querySelector('.live-score');
   const liveLabel = document.querySelector('.score .live-label');
   function setStars(rating) {
    stars.forEach((star, index) => {
     const starValue = index + 1;
     star.classList.toggle('active', starValue <= rating);
     star.querySelector('i').classList.toggle('fa-solid', starValue <= rating);
     star.querySelector('i').classList.toggle('fa-regular', starValue > rating);
    });
    liveScore.textContent = rating;
   }
   setStars(rating);
   const initialLabel = liveLabel.textContent;
   stars.forEach(star => {
    star.addEventListener('mouseenter', function() {
     const value = parseFloat(this.getAttribute('data-value'));
     setStars(value);
     liveLabel.textContent = starDescriptions[value - 1];
    });

    star.addEventListener('click', function() {
    @auth
     const rating = parseFloat(this.getAttribute('data-value'));
     $.ajax({
            url: "{{ route('user.star-rating') }}",
            type: 'GET',
            data: {
                manga_id: "{{ $manga->id }}",
                rating: rating
            },
            success: function(response) {
                console.log(response);
                if(response.status == 'success'){
                    setStars(rating);
                    toastr.success(response.message);
                }else {
                    toastr.warning(response.message);
                }
            },
            error: function() {
                toastr.error('Lỗi hệ thống');
            }
        });
        @else
            toastr.error('Bạn phải đăng nhập để tiếp tục !');
        @endauth
    });

    star.addEventListener('mouseleave', function() {
     setStars(rating);
     liveLabel.textContent = initialLabel;
    });
   });
  });
</script>
