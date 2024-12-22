<?php

namespace Ophim\Core\Controllers\Admin;


use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\Manga;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Helpers\UserHelper;

/**
 * Class MovieCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ChapterCrudController extends CrudController
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

    public function setup()
    {
        CRUD::setModel(\Ophim\Core\Models\Chapter::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/chapter');
        CRUD::setEntityNameStrings('chapter', 'Danh sách chapter');
        $this->crud->denyAccess('update');
        $this->crud->denyAccess('create');
        UserHelper::checkAdminPermissions();
    }

    
    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {   
        // $this->authorize('browse', Chapter::class);
        // CRUD::removeButton('delete');
        CRUD::removeButton('show');

        $this->crud->addButtonFromModelFunction('line', 'preview_server_1', 'previewServer1', 'beginning');
        $this->crud->addButtonFromModelFunction('line', 'preview_server_2', 'previewServer2');
        $this->crud->addButtonFromModelFunction('line', 'chapterActionButtons', 'chapterActionButtons');
        CRUD::orderButtons('line', ['preview_server_1', 'preview_server_2', 'chapterActionButtons', 'delete',]);
        
        $this->crud->with('manga');
        CRUD::addColumn([
            'name' => 'manga_title',
            'label' => 'Thông tin',
            'type' => 'custom_html',
            'value' => function($entry) {
                $statuses = [
                    'waiting_to_upload' => "Đang đợi tải lên Storage",
                    'uploaded_to_storage' => "Đã tải lên Storage",
                ];
                $status = $statuses[$entry->status] ?? 'Không xác định';
                $manga = $entry->manga;
                return '<div style="display: flex; width: 250px;" class="border rounded">
                                <img src="'.$manga->cover.'" class="img-thumbnail" width="65px">
                                <div style="max-width: 182px;  white-space: normal; margin-left:5px">
                                    <b class="pb-2">'.$manga->title.'</b><br>
                                    <b class="text-danger">[Chapter: '.$entry->chapter_number.']</b><br>
                                    <span class="badge bg-info font-weight-normal">'.$status.'</span>
                                </div>
                        </div>';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->whereHas('manga', function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%'); // Ensure this doesn't cause N+1 issues
                });
            }
        ]);

        CRUD::addColumn(['name' => 'updated_at', 'label' => 'Cập nhật lúc', 'type' => 'datetime', 'format' => 'DD/MM/YYYY HH:mm:ss']);

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
                if($value == 'waiting_to_upload'){
                    $this->crud->query->whereNull('status')->orWhere('status', $value);
                } else {
                    $this->crud->query->where('status', $value);
                }   
        });
   
    }

//     protected function setupCreateOperation()
//     {
//         $this->authorize('create', Chapter::class);

//         CRUD::addField(['name' => 'chapter_number', 'label' => 'Tên tập', 'type' => 'number',
//          'attributes' => ['placeholder' => 'Chỉ chấp nhận số nguyên', 'id' => 'chapterNumber',]]);
//          CRUD::addField(['name' => 'title', 'label' => 'Tên chapter', 'type' => 'text',
//          'attributes' => ['placeholder' => 'Tên chapter']]);
    
//     CRUD::addField([  
//         'name' => 'upload_image',
//         'type' => 'view',
//         'view' => 'ophim::mangas.columns.upload_image'
//     ]);
//          CRUD::addField(['name' => 'content', 'label' => 'Nội dung (Các đường dẫn ảnh)', 'type' => 'textarea',
//          'attributes' => [
//             'id' => 'imageUrls',
//             'rows' => 8,
//             'placeholder' => 'http://example.com/images1.jpg
// http://example.com/images2.jpg
// http://example.com/images3.jpg'
//         ],]);

//         CRUD::addField([
//             'name' => 'images_to_upload',
//             'label' => 'Nội dung',
//             'type' => 'hidden', 
//             'attributes' => [
//                 'id' => 'imageToUpload', 
//             ],
//         ]);

//         $rules = [
//             'chapter_number' => 'required|numeric|gt:0',
//             'title' => 'required',
//             'content' => 'required',
//         ];
//         $messages = [
//             'chapter_number.unique' => 'Tên tập đã tồn tại.',
//             'chapter_number.gt' => 'Tên tập phải là số nguyên lớn hơn 0.',
//         ];
//         $this->crud->setValidation($rules, $messages);
//     }

    protected function setupUpdateOperation()
    {
        $this->authorize('update', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));
        // $this->setupCreateOperation();
        // $chapterId = $this->crud->getCurrentEntryId();
        // $chapter = Chapter::find($chapterId);
        // $content =  $chapter->content;
        //  CRUD::addField([   // view
        //     'name' => 'content',
        //     'type' => 'view',
        //     'view' => 'ophim::chapters.inc.content',
        //     'content' => $content
        // ]);
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
       
    }

    protected function getPageNumberFromUrl($url)
    {
        $urlPartsTemp = explode('page_', $url);
        $urlParts = explode('.', end($urlPartsTemp));
        return $urlParts[0];
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        
        $chapter = Chapter::find($id);
        if ($chapter) {
            $chapterOfMangaCrudController = new ChaptersOfMangaCrudController();
            $chapterOfMangaCrudController->deleteImage($chapter);
        }
    
        // Xóa bản ghi
        $res = $this->crud->delete($id);
    
        return $res;
    }

    public function eth(){
        $client = new Client();
    $response = $client->request('GET', 'https://sv1.otruyencdn.com/v1/api/chapter/6591607bac52820f564bf29e');

    $status = $response->getStatusCode();
    $body = json_decode($response->getBody()->getContents(), true);
    // $chapter_detail_data = $body['data']['chapter_image'];
    return response()->json($body['data']);
    }
    
}

