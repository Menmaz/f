<?php

namespace Ophim\Core\Controllers\Admin;

use App\Helpers\ImageHelper;
use Ophim\Core\Requests\MangaRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Tag;
use Ophim\Core\Models\Taxable;
use Ophim\Core\Models\Taxonomy as ModelsTaxonomy;
use Ophim\Crawler\OphimCrawler\NettruyenCrawler;
use Ophim\Crawler\OphimCrawler\TruyenqqCrawler;
use Ophim\Crawler\OphimCrawler\TruyenVNCrawler\TruyenVNCrawler;

/**
 * Class MovieCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MangaCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as backpackStore;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as backpackUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation {
        destroy as traitDestroy;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    use \Ophim\Core\Traits\Operations\BulkDeleteOperation {
        bulkDelete as traitBulkDelete;
    }


    public function setup()
    {
        CRUD::setModel(Manga::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/manga');
        CRUD::setEntityNameStrings('truyện', 'truyện');
        CRUD::setCreateView('ophim::mangas.create',);
        CRUD::setUpdateView('ophim::mangas.edit',);
        $this->crud->enableExportButtons();
        $this->crud->enablePersistentTable();
        $this->crud->disableReorder();
    }

    protected function setupListOperation()
    {
        Widget::add([
            'type'         => 'card',
            'wrapper' => ['class' => 'col-md-12'], // optional
            'class'   => 'card bg-error text-white', // optional
            'content'    => [
                'body'   => 'Khi xóa truyện, tất cả các chapter và thư mục ảnh của nó cũng sẽ bị xóa.',
            ]
        ]);

        CRUD::addButtonFromModelFunction('line', 'show_chapters', 'showChapters', 'beginning');
        $this->crud->addButtonFromModelFunction('line', 'crawl_chapter_button', 'crawlChapterButton');

        $this->crud->addFilter([
            'name'  => 'status',
            'type'  => 'select2',
            'label' => 'Tình trạng'
        ], function () {
            return ModelsTaxonomy::where('type', 'status')->select('name','slug')->pluck('name', 'slug')->toArray();
        }, function ($value) {
            if ($value) {
                 $this->crud->query->where(function ($query) use ($value) {
                    $query->whereHas('statuses', function ($query) use ($value) {
                        $query->where('slug', $value);
                    });
                    if ($value == 'ongoing') {
                        $query->orDoesntHave('statuses');
                    }
                });
            }
        });

        $this->crud->addFilter([
            'name'  => 'type',
            'type'  => 'select2',
            'label' => 'Loại truyện'
        ], function () {
            return ModelsTaxonomy::where('type', 'type')->select('name','slug')->pluck('name', 'slug')->toArray();
        }, function ($value) { 
            if ($value) {
                $this->crud->query = $this->crud->query->whereHas('types', function($query) use ($value) {
                    $query->where('slug', $value);
                });
            }
        });

        $this->crud->addFilter([
            'name'  => 'category',
            'type'  => 'select2',
            'label' => 'Thể loại'
        ], function () {
            return ModelsTaxonomy::where('type', 'genre')->select('name','slug')->pluck('name', 'slug')->toArray();
        }, function ($value) {
            if ($value) {
                $this->crud->query = $this->crud->query->whereHas('genres', function($query) use ($value) {
                    $query->where('slug', $value);
                });
            }
        });
    

        $this->crud->addFilter([
            'name'  => 'showntimes_in_weekday',
            'type'  => 'select2',
            'label' => 'Ngày chiếu truyện'
        ], function () {
            return [
                // '0' => 'Hằng ngày',
                '2' => 'Thứ 2',
                '3' => 'Thứ 3',
                '4' => 'Thứ 4',
                '5' => 'Thứ 5',
                '6' => 'Thứ 6',
                '7' => 'Thứ 7',
                '1' => 'Chủ Nhật',
            ];
        }, function ($val) {
            $this->crud->addClause('where', 'is_shown_in_weekly', true);
            $this->crud->addClause('where', 'showntimes_in_weekday', $val);
        });

        $this->crud->addFilter(
            [
                'type'  => 'simple',
                'name'  => 'is_shown_in_weekly',
                'label' => 'Lịch chiếu truyện'
            ],
            false,
            function () {
                $this->crud->addClause('where', 'is_shown_in_weekly', true);
            }
        );

        $this->crud->addFilter(
            [
                'type'  => 'simple',
                'name'  => 'is_recommended',
                'label' => 'Đề cử'
            ],
            false,
            function () {
                $this->crud->addClause('where', 'is_recommended', true);
            }
        );

        $this->crud->addFilter(
            [
                'type'  => 'simple',
                'name'  => 'has_no_chapter',
                'label' => 'Chưa có chapter'
            ],
            false,
            function ($value) {
                $this->crud->addClause('whereDoesntHave', 'chapters');
            }
        );
        
        $this->crud->addFilter(
            [
                'type'  => 'simple',
                'name'  => 'has_no_genre',
                'label' => 'Chưa có thể loại'
            ],
            false,
            function ($value) {
                $this->crud->addClause('whereDoesntHave', 'genres');
            }
        );

        CRUD::addColumn([
            'title' => 'title',
            'cover' => 'cover',
            'label' => 'Thông tin',
            'lastestChapter' => 'lastestChapter',
            'statuses' => 'statuses',
            'types' => 'types',
            'updated_at' => 'updated_at',
            'type' => 'view',
            'view' => 'ophim::mangas.columns.column_manga_info',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->where('title', 'like', '%' . $searchTerm . '%')->orWhere('slug', 'like', '%' . $searchTerm . '%');
            }
        ]);
        
        // CRUD::addColumn(['name' => 'created_at', 'label' => 'Thêm vào lúc', 'type' => 'datetime', 'format' => 'DD/MM/YYYY']);
        // CRUD::addColumn(['name' => 'updated_at', 'label' => 'Cập nhật lúc', 'type' => 'datetime', 'format' => 'DD/MM/YYYY']);
    
        CRUD::addColumn(['name' => 'user.username', 'label' => 'Cập nhật bởi', 'type' => 'text',
        'value' => function ($entry) {
            return $entry->user()->select('username')->first()->username ?? 'Hệ thống';
        }
        ]);

        CRUD::addColumn([
            'name' => 'genres',
            'label' => 'Thể loại',
            'type' => 'custom_html',
            'value' => function ($entry) {
                $entry->load('genres'); // Ensure genres are loaded
                $genres = $entry->genres;
                $html = '<div class="genres">';
                if ($genres->isNotEmpty()) {
                    $uniqueGenres = $genres->unique('name');
                    $counter = 0;
                    foreach ($uniqueGenres as $genre) {
                        if ($counter > 0 && $counter % 3 == 0) {
                            $html .= '<br>';
                        } elseif ($counter > 0) {
                            $html .= ', ';
                        }
                        $html .= '<span class="badge rounded-pill badge-warning" style="font-size: 0.8rem; margin: 2px 2px; padding: 2px 4px;">' . $genre->name . '</span>';
                        $counter++;
                    }
                } else {
                    $html .= '<span class="badge badge-danger">Chưa có thể loại!</span>';
                }
                $html .= '</div>';
                return $html;
            }
        ]);        
        
        
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function addFields(){
        CRUD::addField(['name' => 'title', 'label' => 'Tên truyện', 'type' => 'text','tab' => 'Thông tin truyện']);
        CRUD::addField(['name' => 'alternative_titles', 'label' => 'Tên truyện (khác)', 'type' => 'text','tab' => 'Thông tin truyện']);
        CRUD::addField(['name' => 'slug', 'label' => 'Đường dẫn tĩnh', 'type' => 'text', 'tab' => 'Thông tin truyện']);
        CRUD::addField(['name' => 'notify', 'label' => 'Thông báo / ghi chú', 'type' => 'text', 'attributes' => ['placeholder' => 'Tuần này hoãn chiếu'],
         'tab' => 'Thông tin truyện']);
        CRUD::addField([
            'name' => 'cover', 'label' => 'Ảnh cover', 'type' => 'ckfinder', 'preview' => ['width' => 'auto', 'height' => '340px'], 'tab' => 'Thông tin truyện'
        ]);
        
        CRUD::addField(['name' => 'description', 'label' => 'Nội dung', 'type' => 'summernote', 'tab' => 'Thông tin truyện']);

        $mangaId = $this->crud->getCurrentEntryId();
        $mangaWithTaxonomies = Manga::with('taxanomies')->find($mangaId);
        $mangaStatusId = null;
        $mangaStatusIdv2 = ModelsTaxonomy::where('slug', 'ongoing')->first()->id;
        $mangaTypeId = null;

        if ($mangaWithTaxonomies !== null && $mangaWithTaxonomies->taxanomies->isNotEmpty()) {
            foreach ($mangaWithTaxonomies->taxanomies as $taxanomie) {
                if ($taxanomie->type == 'type') {
                    $mangaTypeId = $taxanomie->id;
                } else if($taxanomie->type == 'status'){
                    $mangaStatusId = $taxanomie->id;
                }
            }
        }

        $types = ModelsTaxonomy::where('type', 'type')->pluck('name', 'id')->toArray();
        CRUD::addField([
            'name' => 'typev2', 
            'label' => 'Loại truyện', 
            'type' => 'radio', 
            'tab' => 'Phân loại', 
            'inline'      => true, 
            'options' => $types,
            'value' => $mangaTypeId,
        ]);

        $statuses = ModelsTaxonomy::where('type', 'status')->pluck('name', 'id')->toArray();
        CRUD::addField([
            'name' => 'statusv2', 
            'label' => 'Trạng thái', 
            'type' => 'radio', 
            'tab' => 'Phân loại', 
            'inline'      => true, 
            'options' => $statuses,
            'value' => $mangaStatusId ?? $mangaStatusIdv2,
        ]);
         
        $genres = ModelsTaxonomy::where('type', 'genre')->pluck('name', 'id')->toArray();
        $selectedGenres = [];
        if ($mangaWithTaxonomies && $mangaWithTaxonomies->taxanomies) {
            $selectedGenres = $mangaWithTaxonomies->taxanomies->pluck('id')->toArray();
        }
        CRUD::addField([
            'name' => 'genres', 
            'label' => 'Thể loại', 
            'type' => 'checklist', 
            'tab' => 'Phân loại', 
            'number_of_columns' => 4, 
            'options' => function() use ($genres) {
                return $genres;
            },
            'value' => $selectedGenres,
        ]);

        CRUD::addField([
            'name' => 'status',
            'type' => 'hidden',
            'value' => 'publish',
        ]);

        CRUD::addField([
            'name' => 'is_shown_in_weekly',
            'label' => 'Hiện truyện trong lịch chiếu', 
            'type' => 'switch', 
            'color'    => 'primary', // May be any bootstrap color class or an hex color
            'onLabel' => '✓',
            'offLabel' => '✕',
            'tab' => 'Lịch chiếu'
        ]);
        CRUD::addField([
            'name' => 'showntimes_in_weekday', 
            'label' => 'Ngày chiếu truyện hằng tuần', 
            'type' => 'radio', 
            'options'     => [
                // 0 => 'Hằng ngày',
                2 => 'Thứ 2',
                3 => 'Thứ 3',
                4 => 'Thứ 4',
                5 => 'Thứ 5',
                6 => 'Thứ 6',
                7 => 'Thứ 7',
                1 => 'Chủ Nhật',
            ],
            'inline'      => true,
            'default' => 0,
            'attributes' => ['placeholder' => 'định dạng số: 0 = hằng ngày;1= Chủ nhật;2-7=Thứ 2-7'], 
            'tab' => 'Lịch chiếu'
        ]);

        CRUD::addField([
            'name' => 'showntimes_in_day',
            'label' => 'Ngày bắt đầu chiếu truyện',
            'default' => '',
            'type' => 'date',
            'format' => 'DD/MM/YYYY',
            'wrapperAttributes' => [
                'class' => 'form-group col-md-4'
            ], 
            'attributes' => ['placeholder' => 'định dạng ngày: dd/mm/yyyy'], 
            'tab' => 'Lịch chiếu'
        ]);

        CRUD::addField(['name' => 'is_recommended', 'label' => 'Đề cử', 'type' => 'boolean', 'tab' => 'Khác']);

        $rules = [
            'title' => 'required|min:2',
            'slug' => 'required|min:2|unique:mangas,slug',
            'cover' => 'required',
            'description' => 'required',
            // 'typev2' => 'required',
            'statusv2' => 'required',
            'genres' => 'required',
        ];
        $messages = [
            'title.required' => 'Vui lòng nhập tên truyện',
            'slug.required' => "Vui lòng nhập tên đường dẫn tĩnh",
            'slug.unique' => "Đường dẫn tĩnh đã tồn tại ",
            'slug.unique' => "Đường dẫn tĩnh đã tồn tại ",
            'slug.unique' => "Đường dẫn tĩnh đã tồn tại ",
            // 'typev2.required' => "Vui lòng chọn loại truyện",
            'statusv2.required' => "Vui lòng chọn loại trạng thái",
            'genres.required' => "Vui lòng chọn loại thể loại",
        ];
        $this->crud->setValidation($rules, $messages);
    }

    protected function setupCreateOperation()
    {
        $this->authorize('create', Manga::class);

        // CRUD::setValidation(MangaRequest::class);
        $this->addFields();
    }


    protected function setupUpdateOperation()
    {
        $this->authorize('update', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));

        $this->addFields();

        $rules = [
            'title' => 'required|min:2',
            'slug' => 'required|min:2|unique:mangas,slug,'.$this->crud->getCurrentEntryId(),
            'cover' => 'required',
            'description' => 'required',
            // 'typev2' => 'required',
            'statusv2' => 'required',
            'genres' => 'required',
        ];
        $messages = [
            'title.required' => 'Vui lòng nhập tên truyện',
            'slug.required' => "Vui lòng nhập tên đường dẫn tĩnh",
            'slug.unique' => "Đường dẫn tĩnh đã tồn tại ",
            'slug.unique' => "Đường dẫn tĩnh đã tồn tại ",
            'slug.unique' => "Đường dẫn tĩnh đã tồn tại ",
            // 'typev2.required' => "Vui lòng chọn loại truyện",
            'statusv2.required' => "Vui lòng chọn loại trạng thái",
            'genres.required' => "Vui lòng chọn loại thể loại",
        ];
        $this->crud->setValidation($rules, $messages);
    }

    public function store(Request $request)
    {
        $this->getTaxonamies($request);

        return $this->backpackStore();
    }

    public function update(Request $request)
    {
            $this->getTaxonamies($request);
            return $this->backpackUpdate();
    }

    protected function getTaxonamies(Request $request)
{
    try {
        $type = (int) request('typev2', 0);
        $status = (int) request('statusv2', 0);
        $genres = (array) request('genres', []);
        
        if (!empty($status)) {
            $genres[] = $status;
        }
        if (!empty($type)) {
            $genres[] = $type;
        }
        $request['genres'] = $genres;

        $cover = $request['cover'];
        $slug = $request['slug'];
        if($cover && $slug){  
        //     $contaboPrefix = "/uploads/manga/" . $slug . "/cover.webp";
        //     $imageUploader = new ImageStorageManager();
        //     $client = new Client();
        //     $response = $client->request('GET', url($cover));
        //     $status = $response->getStatusCode();
        //     if($status == 200){
        //         $imageData = $response->getBody()->getContents();
        //         $convertedToWebpImage = $imageUploader->convertToWebP($imageData);
        //         $uploadImageToContaboUrl = $imageUploader->uploadImageToContabo($contaboPrefix, $convertedToWebpImage);
        //         $request['cover'] = $uploadImageToContaboUrl;
        //     } 
        $cover = ImageHelper::uploadedMangaThumb($slug, $cover);
        }
        $request['cover'] = $cover;
    } catch (\Throwable $th) {
        $request['cover'] = url($cover);
    }
}

    // protected function setupDeleteOperation()
    // {
    //     $this->authorize('delete', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));
    // }

    public function deleteImage($manga)
    {
        if ($manga->cover && !filter_var($manga->cover, FILTER_VALIDATE_URL) && file_exists(public_path($manga->cover))
         && strpos($manga->cover, $manga->slug) !== false) {
            unlink(public_path($manga->cover));
        } else if($manga->cover && filter_var($manga->cover, FILTER_VALIDATE_URL)) {
            $imageUploader = new ImageStorageManager();
            $contaboPrefix = "/uploads/manga/{$manga->slug}/cover.webp";
            $imageUploader->deleteImageOnContabo($contaboPrefix);
        }
        return true;
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $manga = Manga::find($id);

        $this->deleteImage($manga);

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $res = $this->crud->delete($id);
        if ($res) {
            //xóa các thể loại, loại của truyện
            Taxable::where('taxable_id', $id)->delete();

            //xóa các chapter của truyện
            $chapters = Chapter::where('manga_id', $id)->get();
            $chapterOfMangaCrudController = new ChaptersOfMangaCrudController();
            foreach ($chapters as $chapter) {
                $chapterOfMangaCrudController->deleteImage($chapter);
                $chapter->delete();
            }
            
        }
        return $res;
    }

    public function bulkDelete()
    {
        $this->crud->hasAccessOrFail('bulkDelete');
        $entries = request()->input('entries', []);
        $deletedEntries = [];

        foreach ($entries as $key => $id) {
            if ($entry = $this->crud->model->find($id)) {
                $this->deleteImage($entry);
                $res = $entry->delete();
                if ($res) {
                    Taxable::where('taxable_id', $id)->delete();
                    $chapters = Chapter::where('manga_id', $id)->get();
                    $chapterOfMangaCrudController = new ChaptersOfMangaCrudController();
                    foreach ($chapters as $chapter) {
                        $chapterOfMangaCrudController->deleteImage($chapter);
                        $chapter->delete();
                    }
                }
                $deletedEntries[] = $res;
            }
        }

        return $deletedEntries;
    }

    public function showCrawlChapterPage($manga_slug){
        $manga = Manga::where('slug', $manga_slug)->first();
        return view("ophim::mangas.crawl_chapter_page", compact('manga'));
    }

    private $websiteDomainsForCrawl = [
        'nettruyencc.com' => NettruyenCrawler::class,
        'truyenvn.me' => TruyenVNCrawler::class,
        'truyenqqviet.com' => TruyenqqCrawler::class
    ];

    private function getCrawlerForDomain($url)
    {
        foreach ($this->websiteDomainsForCrawl as $domain => $crawlerClass) {
            if (strpos($url, $domain) !== false) {
                return new $crawlerClass(null);
                break;
            }
        }
        return null;
    }

    public function fetchChapters(Request $request){
        try {
        $mangaUrl = $request['link'];
        $mangaSlug = $request->route('manga_slug');
        $manga = Manga::where('slug', $mangaSlug)->first();
        $crawler = $this->getCrawlerForDomain($mangaUrl);

        if ($crawler) {
            $mangaDataResponse = $crawler->getMangaDetailData($mangaUrl);
            // return response()->json(['error_page_images' => $error_page_images, 'missing_chapters' => $mangaDataResponse]);
            
            if (isset($mangaDataResponse['data']['item'])) {
                $mangaData = $mangaDataResponse['data']['item'];

                $existingChapters = Chapter::where('manga_id', $manga->id)->pluck('chapter_number')->all();
                $newChapters  = [];
                foreach ($mangaData['chapters'] as $chapter) {
                    if (!in_array($chapter['chapter_name'], $existingChapters)) {
                    $newChapters[] = [
                        'chapter_name' => $chapter['chapter_name'],
                        'chapter_api_data' => $chapter['chapter_api_data'],
                        'chapter_updated_at' => $chapter['chapter_updated_at']
                    ];
                }
                }

                return response()->json(['missing_chapters' => $newChapters]);
            }else {
                return response()->json(['error' => 'Truyện không hợp lệ !']);
            }
            $error = $mangaDataResponse['error'];
            if(isset($error)){
                return response()->json(['error' => $error]);
            }
        } else {
            return response()->json(['error' => 'Trang web không hỗ trợ!']);
        }

        // return response()->json(['error' => 'Trang web không hỗ trợ !']);
    } catch (\Throwable $th) {
        return response()->json(['error' => "Lỗi hệ thống: " . $th->getMessage()]);
    }
    }

    public function crawlChapters(Request $request){
        try {
        $chapterUrl = $request['link'];
        $mangaSlug = $request->route('manga_slug');
        $chapterNumber = $request->route('chapter_number');
        $chapterUpdatedAt = $request->input('chapter_updated_at');
        $manga = Manga::where('slug', $mangaSlug)->first();
        $crawler = $this->getCrawlerForDomain($chapterUrl);

        if ($crawler) {
            $crawler->createNewChapter($manga, $chapterUrl, $chapterNumber, $chapterUpdatedAt);
            return response()->json(['message' => "Đã thêm mới chapter cho truyện {$manga->title}"]);
        }

        return response()->json(['message' => "Đã thêm mới chapter {$chapterNumber} cho truyện {$manga->title}"]);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()]);
    }
    }
}
