<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Ophim\Core\Helpers\UserHelper;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\DB as FacadesDB;

class TermsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation {
        store as backpackStore;
    }
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
        CRUD::setModel(\Ophim\Core\Models\Setting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/terms');
        CRUD::setEntityNameStrings('Điều khoản và chính sách', 'Điều khoản và chính sách');
        $this->crud->query->where('key', 'terms');
        $this->crud->denyAccess('create');
        $this->crud->denyAccess('delete');
        UserHelper::checkAdminPermissions();
    }

    protected function setupListOperation()
    {
        CRUD::addColumn([
            'name' => 'content',
            'label' => 'Nội dung',
            'type' => 'custom_html',
            'value' => function($entry) {
                return '<div class="post-content" style="width: 1200px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;">' . $entry->value . '</div>';
            },
        ]);
    }

    protected function addFields()
    {
        CRUD::addField([
            'name' => 'value',
            'label' => '',
            'type' => 'summernote',
            'wrapper' => ['class' => 'col-sm-12 text-dark custom-summernote-wrapper'],
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->addFields();
    }

    protected function setupUpdateOperation()
    {
        $this->addFields();
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    public function update(Request $request)
    {
        $data = $request->all();
        FacadesDB::table('settings')->where('key', 'terms')->update(['value' => $data['value']]);

        return redirect()->back();
    }
}
