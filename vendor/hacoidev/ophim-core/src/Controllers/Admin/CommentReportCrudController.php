<?php

namespace Ophim\Core\Controllers\Admin;

use App\Helpers\DateHelper;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Ophim\Core\Helpers\UserHelper;

/**
 * Class EpisodeCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CommentReportCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;

    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation {
        update as backpackUpdate;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    use \Ophim\Core\Traits\Operations\BulkDeleteOperation {
        bulkDelete as traitBulkDelete;
    }

    public function setup()
    {
        CRUD::setModel(\Ophim\Core\Models\CommentReport::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/comment_report');
        CRUD::setEntityNameStrings('Bình luận spam', 'Bình luận spam');
        $this->crud->denyAccess('create');
        $this->crud->denyAccess('show');
        $this->crud->denyAccess('update');

        UserHelper::checkAdminPermissions();
    }

    protected function setupListOperation()
    {
        $this->crud->enableExportButtons();

        CRUD::addColumn([
            'name' => 'manga_title',
            'label' => 'Thông tin',
            'type' => 'custom_html',
            'value' => function($entry) {
                $manga = $entry->manga;
                $cleanContent = strip_tags($entry->comment->content);
                return '
                <div class="card text-white bg-primary mb-3" style=" white-space: normal;text-align:center">
                <div class="card-header">
                <b>Truyện</b>: <a href="https://10truyen.com/truyen/' .$manga->slug. '" target="_blank" class="card-title text-white">'.$manga->title.'</a>
                </div>
                <ul class="list-group list-group-horizontal-sm list-group-dark">
                <li class="list-group-item list-group-item-primary"><b>Nội dung bình luận</b>: '.$cleanContent.'</li>
                <li class="list-group-item list-group-item-primary"><b>Người bình luận</b>: '.$entry->user->username.'</li>
                <li class="list-group-item list-group-item-primary"><b>Thời gian bình luận</b>: '.DateHelper::localizeDate($entry->created_at).'</li>
                </ul>
              </div>';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->whereHas('manga', function ($q) use ($searchTerm) {
                    $q->where('title', 'like', '%' . $searchTerm . '%'); 
                });
            }
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        abort(404);
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
    }
}
