<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;
use Ophim\Core\Helpers\UserHelper;
use Ophim\Core\Models\Badge;

/**
 * Class EpisodeCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AdsCrudController extends CrudController
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
        CRUD::setModel(\Ophim\Core\Models\Ad::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/ads');
        CRUD::setEntityNameStrings('Quảng cáo', 'Quảng cáo');
        UserHelper::checkAdminPermissions();
    }

    protected function setupListOperation()
    {
        CRUD::addColumn(['name' => 'name', 'label' => 'Tên', 'type' => 'text']);

        CRUD::addColumn([
            'name' => 'custom_column',
            'label' => 'Xem trước',
            'type' => 'custom_html',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            },
            'value' => function($entry) {
                return "<a  href='{$entry->link}' rel='nofollow' target='_blank'><img style='width: 60%; height: auto;' src='" . asset($entry->image) . "'></a>";
            }
        ]);

        CRUD::addColumn([
            'name' => 'identifier', 
            'label' => 'Vị trí đặt', 
            'type' => 'text',
            'value' => function($entry) {
                $options = [
                    'home' => 'Trang chủ',
                    'latest-mangas' => 'Trang truyện mới',
                    'latest-update-mangas' => 'Trang truyện mới cập nhật',
                    'mangas-by-category' => 'Trang truyện theo thể loại',
                    'manga-detail' => 'Trang chi tiết truyện',
                    'chapter-detail' => 'Trang đọc truyện',
                ];
                return $options[$entry->identifier] ?? 'Không xác định được';
            },
        ]);
    }

    protected function addFields(){
        CRUD::addField(['name' => 'name', 'label' => 'Tên', 'type' => 'text']);
        CRUD::addField(['name' => 'link', 'label' => 'Link', 'type' => 'text']);
        CRUD::addField([
            'name' => 'image', 'label' => 'Ảnh', 'type' => 'ckfinder', 'preview' => ['width' => 'auto', 'height' => '340px']
        ]);
        CRUD::addField([
            'name' => 'identifier', 
            'label' => 'Vị trí đặt', 
            'type'  => 'select_from_array',
            'options' => [
                'home' => 'Trang chủ',
                'latest-mangas' => 'Trang truyện mới',
                'latest-update-mangas' => 'Trang truyện mới cập nhật',
                'mangas-by-category' => 'Trang truyện theo thể loại',
                'manga-detail' => 'Trang chi tiết truyện',
                'chapter-detail' => 'Trang đọc truyện',
            ],
        ]);
        CRUD::addField([
            'name' => 'active',
            'label' => 'Hiện quảng cáo', 
            'type' => 'switch', 
            'color'    => 'primary',
            'onLabel' => '✓',
            'offLabel' => '✕',
            'default' => true,
        ]);
    }

    protected function setupCreateOperation()
    {
        $this->addFields();
        $rules = [
            'name' => 'required',
            'link' => 'required',
            'image' => 'required',
        ];

        $this->crud->setValidation($rules);
    }

    protected function setupUpdateOperation()
    {
        $this->addFields();
        $rules = [
            'name' => 'required',
            'link' => 'required',
            'image' => 'required',
        ];

        $this->crud->setValidation($rules);
    }




    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
   

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

}
