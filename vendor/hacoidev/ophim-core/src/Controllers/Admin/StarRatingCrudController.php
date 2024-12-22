<?php

namespace Ophim\Core\Controllers\Admin;

use App\Helpers\DateHelper;
use Ophim\Core\Requests\MovieRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Ophim\Core\Helpers\UserHelper;
use Ophim\Core\Models\CommentReaction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MovieCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StarRatingCrudController extends CrudController
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
        CRUD::setModel(\Ophim\Core\Models\StarRating::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/star_rating');
        CRUD::setEntityNameStrings('Đánh Giá Sao', 'Đánh Giá Sao');
        // CRUD::setUpdateView('ophim::comments.edit',);
        $this->crud->denyAccess('create');
        $this->crud->denyAccess('update');

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
        CRUD::addColumn([
            'name' => 'manga_title',
            'label' => 'Thông tin',
            'type' => 'custom_html',
            'value' => function($entry) {
                $manga = $entry->manga ?? null;
                $star = $entry->rating ?? 0;
                $star_html = '';

                for ($i = 0; $i < $star; $i++) {
                    $star_html .= '<i class="nav-icon la la-star" style="color: #FFC107;"></i>';
                }

                $created_at = $entry->created_at ?? null;
                $localized_date = $created_at ? DateHelper::localizeDate($created_at) : '';

                if ($manga && $created_at) {
                    return '
                        <div class="card text-white bg-primary mb-3" style="white-space: normal;text-align:center">
                            <div class="card-header">
                                <b>Truyện</b>: <a href="' . route('manga.detail', ['slug' => $manga->slug ]) . '" target="_blank" class="card-title text-white">' . $manga->title . '</a>
                            </div>
                            <ul class="list-group list-group-horizontal-sm">
                                <li class="list-group-item list-group-item-primary"><b>Số sao đánh giá</b>: ' . $star_html . '</li>
                                <li class="list-group-item list-group-item-primary"><b>Người đánh giá</b>: ' . $entry->user->username . '</li>
                                <li class="list-group-item list-group-item-primary"><b>Thời gian đánh giá</b>: ' . $localized_date . '</li>
                            </ul>
                        </div>';
                } 
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->whereHas('manga', function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%'); 
                });
            },
            
        ]);
        
        CRUD::addColumn(['name' => 'updated_at', 'label' => 'Thời gian', 'type' => 'datetime', 'format' => 'DD/MM/YYYY HH:mm:ss']);
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

    // public function destroy($id)
    // {
    //     $this->crud->hasAccessOrFail('delete');
    //     $movie = StarRating::find($id);

    //     $this->deleteImage($movie);

    //     // get entry ID from Request (makes sure its the last ID for nested resources)
    //     $id = $this->crud->getCurrentEntryId() ?? $id;

    //     $res = $this->crud->delete($id);
    //     if ($res) {
    //     }
    //     return $res;
    // }

    public function bulkDelete()
    {
        $this->crud->hasAccessOrFail('bulkDelete');
        $entries = request()->input('entries', []);
        $deletedEntries = [];

        foreach ($entries as $key => $id) {
            if ($entry = $this->crud->model->find($id)) {
                $deletedEntries[] = $entry->delete();
            }
        }

        return $deletedEntries;
    }
}
