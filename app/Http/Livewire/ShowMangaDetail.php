<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Ophim\Core\Models\Manga;

class ShowMangaDetail extends Component
{
    public $manga;
    public $slug;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->manga = Manga::where('slug', $slug)->first();
    }

    public function changeChapter($slug){
        $this->slug = $slug;
        $this->manga = Manga::where('slug', $slug)->first();
    }
    
    public function render()
    {
        return view('livewire.show-manga-detail');
    }
}
