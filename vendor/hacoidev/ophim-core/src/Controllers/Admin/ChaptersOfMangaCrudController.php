<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Class MovieCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ChaptersOfMangaCrudController extends CrudController
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

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */

    public $manga;
    public function setup()
    {
        CRUD::setModel(\Ophim\Core\Models\Chapter::class);
        $mangaSlug = $this->crud->getRequest()->route('manga_slug');
        $manga = Manga::where('slug', $mangaSlug)->select('id', 'title', 'slug')->first();
        $this->manga = $manga;
        $this->crud->query->where('manga_id', $manga->id)->orderByDesc('chapter_number');
        CRUD::setRoute(config('backpack.base.route_prefix') . '/chapters/'. $mangaSlug);
        CRUD::setEntityNameStrings('chapter', 'Danh sách chapter - "'  . $manga->title . '"');
        $this->crud->setHeading("Thêm chapter mới cho '$manga->title' ", 'create');
        $this->crud->setHeading("Sửa chapter cho '$manga->title' ", 'edit');
    }

    
    protected function setupListOperation()
    {   
        // CRUD::removeButton('delete');
        CRUD::removeButton('show');
        // $this->authorize('browse', Movie::class);
        $this->crud->addButtonFromModelFunction('line', 'preview_server_1', 'previewServer1', 'beginning');
        $this->crud->addButtonFromModelFunction('line', 'preview_server_2', 'previewServer2');
        CRUD::orderButtons('line', ['preview_server_1', 'preview_server_2', 'update']);
        
        CRUD::addColumn([
            'name' => 'chapter_number',
            'label' => 'Tên tập',
            'type' => 'text',
            'value' => function($entry) {
                return 'Chương ' . $entry->chapter_number;
            }
        ]);
        
        CRUD::addColumn([
            'name' => 'content',
            'label' => 'Số trang',
            'type' => 'number',
            'value' => function($entry) {
                $contentArray = $entry->content;
                if($contentArray){
                    return count($contentArray);
                } else {
                    return 0;
                }
                
            }
        ]);

        CRUD::addColumn([
            'name' => 'status',
            'label' => 'Tình trạng',
            'type' => 'text',
            'value' => function($entry) {
                $statuses = [
                    'waiting_to_upload' => "Đang đợi tải lên Storage",
                    'uploaded_to_storage' => "Đã tải lên Storage",
                ];
                return $statuses[$entry->status] ?? 'Không xác định';
            }
        ]);

        $this->crud->addFilter([
            'name'  => 'status',
            'type'  => 'select2',
            'label' => 'Tình trạng'
        ], function () {
            return [
                'waiting_to_upload' => "Đang đợi tải lên Storage",
                'uploaded_to_storage' => "Đã tải lên Storage",
            ];
        }, function ($value) {
                $this->crud->query->where('status', $value);
        });
   
        CRUD::addColumn([
            'name' => 'created_at',
            'label' => 'Thêm vào lúc',
        ]);
    }

    protected function addFields(){
        Widget::add([
            'type'         => 'card',
            'wrapper' => ['class' => 'col-md-8'], 
            'class'   => 'card bg-green text-white', 
            'content'    => [
                'header' => 'Lưu ý !',
                'body'   => '- Có thể dán đường dẫn ảnh từ internet hoặc tải ảnh lên thông qua nút chọn tệp dưới đây.
                <br>- Cách lấy đường dẫn từ internet: Click chuột phải vào ảnh và sao chép địa chỉ hình ảnh.
                <br>- Tên ảnh hợp lệ: 1.jpg, 2.png, ..., hệ thống sẽ sắp xếp tăng dần',
            ]
        ]);
        
        // CRUD::setValidation(MovieRequest::class);
        CRUD::addField(['name' => 'chapter_number', 'label' => 'Chương', 'type' => 'text',
         'attributes' => ['placeholder' => 'Chỉ chấp nhận số nguyên', 'id' => 'chapterNumber',]]);
        CRUD::addField(['name' => 'title', 'label' => 'Tên', 'type' => 'text',
         'attributes' => ['placeholder' => 'Tên chapter']]);
        CRUD::addField([   // view
            'name' => 'upload_image',
            'type' => 'view',
            'view' => 'ophim::chapters.inc.upload_image',
            'manga' => $this->manga,
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->authorize('create', Chapter::class);

        $this->addFields();
         CRUD::addField(['name' => 'content', 'label' => 'Nội dung (Các đường dẫn ảnh)', 'type' => 'textarea',
         'attributes' => [
            'id' => 'imageUrls',
            'rows' => 8,
            'placeholder' => 'http://example.com/page_1.jpg
http://example.com/page_2.png'
        ],]);

        CRUD::addField(['name' => 'content_sv2', 'type' => 'hidden',]);
        CRUD::addField(['name' => 'manga_id', 'type' => 'hidden',]);
        CRUD::addField(['name' => 'status', 'type' => 'hidden',]);

        CRUD::addField([
            'name' => 'images_to_upload',
            'label' => 'Nội dung',
            'type' => 'hidden', 
            'attributes' => [
                'id' => 'imageToUpload', 
            ],
        ]);

        $rules = [
            'chapter_number' => 'required|numeric|gt:0',
            'content' => 'required',
        ];
        $messages = [
            'chapter_number.unique' => 'Tên tập đã tồn tại.',
            'chapter_number.gt' => 'Tên tập phải là số nguyên lớn hơn 0.',
        ];
        $this->crud->setValidation($rules, $messages);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->authorize('update', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));
        
        $this->addFields();
        
        $chapterId = $this->crud->getCurrentEntryId();
        $chapter = Chapter::find($chapterId);
        $content =  $chapter->content ?? [];
        $contentString = implode("\n", $content);

        CRUD::addField(['name' => 'old_content', 'type' => 'hidden', 'value' => $contentString]);

        CRUD::addField(['name' => 'content', 'label' => 'Nội dung (Các đường dẫn ảnh) - Post ảnh không có dấu cách, không dấu phảy gì cả.', 'type' => 'textarea',
         'attributes' => [
            'id' => 'imageUrls',
            'rows' => 8,
            'placeholder' => 'http://example.com/page_1.jpg
http://example.com/page_2.webp
http://example.com/page_3.png'
        ],'value' => $contentString]);

    }

    protected function getPageNumberOfChapterUrl($chapterUrl){
        $urlPartsTemp = explode('page_', $chapterUrl);
        $urlParts = explode('.', end($urlPartsTemp));
        $pageNumber = $urlParts[0];
        return $pageNumber;
    }

    public function store(Request $request)
    {
        $chapterNumber = request('chapter_number');
        $imageUrls = explode("\r\n", request('content'));
        $request['content'] = $imageUrls;
        $request['content_sv2'] = $imageUrls;
        $request['manga_id'] = $this->manga->id;
        $request['status'] = 'waiting_to_upload';
        $this->manga->update(['updated_at' => date('Y-m-d H:i:s')]);

        $this->getTaxonamies($request);
        return $this->backpackStore();
    }

    public function update(Request $request)
    {
        $chapterNumber = request('chapter_number');
        $imageUrls = explode("\r\n", request('content'));
        $request['content'] = $imageUrls;
        $request['content_sv2'] = $imageUrls;
        $this->manga->update(['updated_at' => date('Y-m-d H:i:s')]);

        $this->getTaxonamies($request);
        return $this->backpackUpdate();
    }

    protected function getTaxonamies(Request $request)
    {
        Artisan::call('optimize:clear');
    }

    // protected function setupDeleteOperation()
    // {
    //     $this->authorize('delete', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));
    // }

    public function deleteImage($chapter)
    {
        try {
            $imageUploader = new ImageStorageManager();
            $manga = Manga::where('id', $chapter->manga_id)->first();
            $chapterNumber = $chapter->chapter_number;
            $manga_slug = $this->manga->slug;

            $directory = "uploads/manga/{$manga_slug}/chapter_{$chapterNumber}";
            
            // Xóa thư mục trong storage/app/public
            Storage::disk('public')->deleteDirectory($directory);
        
            // Xóa thư mục trong public/storage (nếu cần)

        } catch (\Throwable $th) {
            return false;
        }
    }


    public function traitDestroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        
        $chapter = Chapter::where('id', $id)->first();
         $this->deleteImage($chapter);
    
        // Xóa bản ghi
        $res = $this->crud->delete($id);
        Artisan::call('optimize:clear');
    
        return $res;
    }
    
    public function uploadImages(Request $request)
    {
        $uploadedUrls = [];
        
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            $chapter_number = $request->input('chapter_number');
            $manga_slug = $this->manga->slug;
    
            foreach ($files as $file) {
                if ($file->isValid() && $file->getClientOriginalExtension() && in_array($file->getClientOriginalExtension(), ['jpg', 'png', 'webp'])) {
                    $imageName = $file->getClientOriginalName();
                    $file->storeAs('uploads/manga/'.$manga_slug.'/chapter_'.$chapter_number.'', $imageName, 'public');
                    $imagePath = '/storage/uploads/manga/' . $manga_slug . '/chapter_' . $chapter_number . '/' . $imageName;
                    $uploadedUrls[] = $imagePath;
                }
            }
            
            return response()->json(['message' => 'Images uploaded successfully.', 'image_urls' => $uploadedUrls]);
        } else {
            return response()->json(['error' => 'No images found in request.']);
        }
    }

    function deleteInStorage($imageUrl)
    {
    $filePath = str_replace(asset('storage'), 'public', $imageUrl);
    Storage::delete($filePath);
    }
    
}

