<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Ophim\Core\Models\Manga;

class Rating extends Component
{
    public $manga;
    public $averageStarRating;
    public $rating;
    public $starDescriptions = ['Dở tệ', 'Không hay lắm', 'Cũng được', 'Hay', 'Tuyệt vời'];

    protected $listeners = ['rate'];

    public function mount(Manga $manga)
    {
        $this->manga = $manga;
        $this->averageStarRating = number_format($manga->star_ratings_avg_rating, 2);
        $this->rating = round($manga->star_ratings_avg_rating);
    }

    public function rate($value)
    {
        $this->rating = $value;
        $this->averageStarRating = number_format($this->rating, 2);
    }

    public function render()
    {
        return view('frontend-web.manga-detail.components.rating', [
            'manga' => $this->manga,
            'averageStarRating' => $this->averageStarRating,
            'rating' => $this->rating,
            'starDescriptions' => $this->starDescriptions,
        ]);
    }
}

