<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Ophim\Core\Helpers\UserHelper;
use Ophim\Core\Models\Chapter;
use Ophim\Core\Models\ChapterReport;
use Ophim\Core\Models\Episode;
use Ophim\Core\Models\Manga;

/**
 * Class EpisodeCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ChapterReportCrudController extends CrudController
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
        CRUD::setModel(\Ophim\Core\Models\ChapterReport::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/chapter_report');
        CRUD::setEntityNameStrings('Truyện lỗi', 'Truyện lỗi');
        $this->crud->addButtonFromModelFunction('line', 'open_episode', 'openEpisode', 'beginning');
        $this->crud->addButtonFromModelFunction('line', 'open_chapter_in_web', 'openChapterInWeb', 'beginning');
        $this->crud->denyAccess('create');
        $this->crud->denyAccess('show');
        $this->crud->denyAccess('delete');
        $this->crud->denyAccess('update');

        UserHelper::checkAdminPermissions();
    }

    protected function setupListOperation()
    {
        // $this->authorize('browse', ChapterReport::class);

        $this->crud->enableExportButtons();
        // $this->crud->addClause('where', 'has_report', true);

        CRUD::addColumn([
            'name' => 'manga_title',
            'label' => 'Truyện',
            'type' => 'custom_html',
            'value' => function($entry) {
                return '<span>'.$entry->chapter->manga->title.'</span>';
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('chapter.manga', function ($query) use ($searchTerm) {
                    $query->where('title', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);
        // CRUD::addColumn(['name' => 'chapter_id', 'label' => 'Tập', 'type' => 'text']);
        // CRUD::addColumn(['name' => 'link', 'label' => 'Link', 'type' => 'textarea']);
        CRUD::addColumn(['name' => 'report_message', 'label' => 'Tin nhắn', 'type' => 'textarea']);
        CRUD::addColumn(['name' => 'created_at', 'label' => 'Báo cáo vào lúc', 'type' => 'datetime', 'format' => 'DD/MM/YYYY HH:mm:ss']);
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

        // CRUD::addField(['name' => 'has_report', 'label' => 'Đánh dấu đang lỗi', 'type' => 'checkbox']);
        CRUD::addField(['name' => 'report_message', 'label' => 'Tin nhắn báo cáo', 'type' => 'textarea']);
    }

    // public function bulkDelete()
    // {
    //     $this->crud->hasAccessOrFail('bulkDelete');
    //     $entries = request()->input('entries', []);
    //     $deletedEntries = [];

    //     foreach ($entries as $key => $id) {
    //         if ($entry = $this->crud->model->find($id)) {
    //             $deletedEntries[] = $entry->update(['has_report' => 0, 'report_message' => '']);
    //         }
    //     }

    //     return $deletedEntries;
    // }
}
