<?php

namespace Ophim\Core\Controllers\Admin;

use Ophim\Core\Requests\CategoryRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Ophim\Core\Helpers\UserHelper;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Taxonomy;

/**
 * Class CategoryCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CategoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\Ophim\Core\Models\Taxonomy::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/category');
        CRUD::setEntityNameStrings('Thể Loại', 'Thể Loại');
        // $this->crud->query = $this->crud->query->orderBy('type', 'asc');
        // $this->crud->addClause('orderBy', 'name', 'ASC');
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
        // $this->authorize('browse', Category::class);

        $this->crud->query = $this->crud->query->where('type', 'genre')->orWhere('type', 'type');
        CRUD::column('name')->label('Tên')->type('text');
        CRUD::column('slug')->label('Đường dẫn tĩnh')->type('text');
        CRUD::column('seo_title')->label('SEO Title')->type('text');
        CRUD::column('seo_des')->label('SEO Description')->type('text');
        CRUD::column('seo_key')->label('SEO Keyword')->type('text');

        $this->crud->addFilter([
            'name'  => 'order_by_alpha',
            'type'  => 'select2',
            'label' => 'Sắp xếp theo thứ tự chữ cái'
        ], function () {
            return [
                'asc' => 'A-Z (Tăng dần)',
                'desc' => 'Z-A (Giảm dần)',
            ];
        }, function ($val) {
            $this->crud->orderBy('name', $val);
        });

        $this->crud->addFilter([
            'name'  => 'first_letter',
            'type'  => 'select2',
            'label' => 'Lọc theo chữ cái đầu'
        ], function () {
            $letters = range('A', 'Z');
            return array_combine($letters, $letters);
        }, function ($value) {
            // Apply filter to the query
            $this->crud->addClause('where', 'name', 'like', $value . '%');
        });

        // Default sorting
        // if (!$this->crud->getRequest()->has('order_by_alpha')) {
        //     $this->crud->orderBy('name', 'asc');
        // }
        
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        $this->authorize('create', Taxonomy::class);

        CRUD::setValidation(CategoryRequest::class);

        CRUD::field('name')->label('Tên')->type('text');
        CRUD::field('seo_title')->label('Tên')->type('text');
        CRUD::field('slug')->label('Đường dẫn tĩnh')->type('text');
        CRUD::field('seo_title')->label('SEO Tiêu đề')->type('text');
        CRUD::field('seo_des')->label('SEO Mô tả')->type('textarea');
        CRUD::field('seo_key')->label('SEO Từ khóa')->type('text');
        CRUD::addField([
            'name' => 'type',
            'type' => 'hidden',
            'value' => 'genre',
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        // $this->authorize('update', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));

        $this->setupCreateOperation();
    }

    // protected function setupDeleteOperation()
    // {
    //     $this->authorize('delete', $this->crud->getEntryWithLocale($this->crud->getCurrentEntryId()));
    // }
}
