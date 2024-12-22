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
class IconRatingCrudController extends CrudController
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
        CRUD::setModel(\Ophim\Core\Models\IconRating::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/icon_rating');
        CRUD::setEntityNameStrings('Đánh Giá Bằng Icon', 'Đánh Giá Bằng Icon');
        $this->crud->orderBy('created_at', 'desc');
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
        $icons = [
            'like' => [
                'name' => 'Like',
                'image' => 'https://10truyen.com/_next/static/media/gif2.dd09587a.gif',
            ],
            'buon-cuoi' => [
                'name' => 'Buồn Cười',
                'image' => 'https://10truyen.com/_next/static/media/gif1.c8949ebc.gif',
            ],
            'tuyet-voi' => [
                'name' => 'Tuyệt Vời',
                'image' => 'https://10truyen.com/_next/static/media/gif3.5d56111b.gif',
            ],
            'ngac-nhien' => [
                'name' => 'Ngạc Nhiên',
                'image' => 'https://10truyen.com/_next/static/media/gif4.4e079044.gif',
            ],
            'buon' => [
                'name' => 'Buồn',
                'image' => 'https://10truyen.com/_next/static/media/gif5.576fcf4d.gif',
            ],
            'tuc-gian' => [
                'name' => 'Tức Giận',
                'image' => 'https://10truyen.com/_next/static/media/gif6.7f6cdd48.gif',
            ],
        ];

        CRUD::addColumn([
            'name' => 'manga_title',
            'label' => 'Thông tin',
            'type' => 'custom_html',
            'value' => function($entry) use ($icons) {
                $manga = $entry->manga;
                $iconKey = $entry->icon; 
                $icon = $icons[$iconKey]; 
                $imageSrc = $icon['image'];
                $iconName = $icon['name'];

                return '
                <div class="card text-white bg-primary mb-3" style="white-space: normal;text-align:center">
                <div class="card-header">
                <b>Truyện</b>: <a class="text-break text-white" href="https://10truyen.com/truyen/' .$manga->slug. '" target="_blank" class="card-title">'.$manga->title.'</a>
                </div>
                <ul class="list-group list-group-horizontal-sm list-group-dark">
                <li class="list-group-item list-group-item-primary"><b>Icon đánh giá  </b>: <img class="border border-primary rounded-sm" alt="gif" draggable="false" width="40" height="40" src="'.$imageSrc.'"/>('.$iconName.')
                <li class="list-group-item list-group-item-primary"><b>Người đánh giá</b>:  '.$entry->user->username.'</li>
                <li class="list-group-item list-group-item-primary"><b>Thời gian đánh giá</b>:'.DateHelper::localizeDate($entry->created_at).'</li>
                </ul>
              </div>';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->whereHas('manga', function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%'); 
                });
            }
        ]);

        // CRUD::addColumn(['name' => 'updated_at', 'label' => 'Thời gian', 'type' => 'datetime', 'format' => 'DD/MM/YYYY HH:mm:ss']);
    }

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
