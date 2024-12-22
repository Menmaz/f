<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Ophim\Core\Helpers\UserHelper;
use Illuminate\Support\Str;
use Ophim\Core\Models\Badge;

/**
 * Class EpisodeCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BadgeCrudController extends CrudController
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
        CRUD::setModel(\Ophim\Core\Models\Badge::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/badge');
        CRUD::setEntityNameStrings('Huy hiệu', 'Huy hiệu');
        UserHelper::checkAdminPermissions();
    }

    protected function setupListOperation()
    {
        // $this->crud->enableExportButtons();

        CRUD::addColumn([
            'name' => 'image',
            'label' => 'Ảnh',
            'type' => 'image',
            // 'height' => '80%',
            // 'width'  => '100%',
        ]);

        CRUD::addColumn([
            'name' => 'custom_column',
            'label' => 'Tên phụ với màu nền',
            'type' => 'custom_html',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%');
            },
            'value' => function($entry) {
                return "<div class='btn' style='background-color: {$entry->css_color}; border: 2px solid rgba(255,255,255, 0.5); width: 20%; padding: 5px; text-align: center; color: #fff;font-size:15px'>
                            {$entry->name}
                        </div>";
            }
        ]);

        CRUD::addColumn(['name' => 'comment_threshold', 'label' => 'Mục tiêu', 'type' => 'text']);
    }

    protected function addFields(){
        CRUD::addField([
            'name' => 'image', 'label' => 'Ảnh', 'type' => 'ckfinder', 'preview' => ['width' => 'auto', 'height' => '340px']
        ]);
        CRUD::addField(['name' => 'comment_threshold', 'label' => 'Mục tiêu', 'type' => 'number']);
        CRUD::addField(['name' => 'name', 'label' => 'Tên phụ', 'type' => 'text', 'attributes' => ['id' => 'badgeNameInput'],]);
        CRUD::addField([
            'name' => 'css_color', 'label' => 'Màu nền của tên phụ', 'type' => 'text', 'attributes' => ['id' => 'cssColorText',
            'placeholder' => 'Mã màu CSS']
        ]);
    }

    protected function setupCreateOperation()
    {
        Widget::add([
            'type'         => 'card',
            'wrapper' => ['class' => 'col-md-8'], // optional
            'class'   => 'card bg-success text-white', // optional
            'content'    => [
                'body'   => 'Khi bạn đặt tên phụ là Admin, Mod hoặc Đội Sub thì không cần điền mục tiêu.',
            ]
        ]);

        CRUD::addField(['name' => 'comment_threshold', 'label' => 'Mục tiêu', 'type' => 'number']);
        $this->addFields();
        CRUD::addField(['name' => 'name', 'label' => 'Tên phụ', 'type' => 'text', 'attributes' => ['id' => 'badgeNameInput'],]);

        CRUD::addField([ 
            'name' => 'preview_css_color',
            'type' => 'view',
            'view' => 'ophim::badge.fields.choose_css_color',
        ]);

        $rules = [
            'image' => 'required',
            'name' => 'required',
            'css_color' => 'required'
        ];

        $messages = [
            'comment_threshold.gt' => 'Mục tiêu phải là số nguyên lớn hơn 0.',
        ];
        $this->crud->setValidation($rules, $messages);
    }

    public function store(Request $request)
{
    // $maxCommentThreshold = Badge::max('comment_threshold');
    $rules = [
        'name' => 'required',
        'css_color' => 'required'
    ];
    // Xác định các tên không yêu cầu `comment_threshold`
    $exemptedNames = ['Admin', 'Mod', 'Đội Sub'];
    $name = $this->crud->getRequest()->input('name');
    $commentThreshold = $this->crud->getRequest()->input('comment_threshold');
    // Nếu `name` không thuộc các giá trị ngoại lệ, thêm quy tắc `comment_threshold`
    if (in_array($name, $exemptedNames)) {
        if (!empty($commentThreshold)) {  // Nếu `comment_threshold` không trống
                // $fail('Không được điền mục tiêu khi tên là Admin, Mod hoặc Đội Sub.');
                $rules['comment_threshold'] = [
                    function ($attribute, $value, $fail) {
                        $fail('Không được điền mục tiêu khi tên phụ là Admin, Mod hoặc Đội Sub.');
                    },
                ];
        }
    } else {
        // Nếu tên không thuộc các ngoại lệ, áp dụng các quy tắc cho `comment_threshold`
        // $maxCommentThreshold = Badge::max('comment_threshold');
        // $rules['comment_threshold'] = [
        //     'required',
        //     'numeric',
        //     'gt:0',
        //     function ($attribute, $value, $fail) use ($maxCommentThreshold) {
        //         if ($value < 2 * $maxCommentThreshold) {
        //             $requiredValue = 2 * $maxCommentThreshold;
        //             $fail("Mục tiêu ít nhất phải gấp đôi so với mục tiêu của huy hiệu trước đó. (>= {$requiredValue})");
        //         }
        //     },
        // ];
    }

    $messages = [
        'comment_threshold.gt' => 'Mục tiêu phải là số nguyên lớn hơn 0.',
        'comment_threshold.required' => 'Mục tiêu là bắt buộc, trừ khi tên phụ là Admin, Mod hoặc Đội Sub.',
    ];
    $this->crud->setValidation($rules, $messages);

    return $this->backpackStore();
}


    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->addFields();
        CRUD::addField([ 
            'name' => 'preview_css_color',
            'type' => 'view',
            'view' => 'ophim::badge.fields.choose_css_color',
            'badge' => $this->crud->getCurrentEntry()
        ]);

        $rules = [
            // 'name' => 'required',
            'image' => 'required',
            'css_color' => 'required',
        ];
        $messages = [
        ];
        $this->crud->setValidation($rules, $messages);
    }

    public function update(Request $request)
{
    $currentBadge = Badge::find($this->crud->getCurrentEntryId());
    // $currentCommentThreshold = $currentBadge ? $currentBadge->comment_threshold : null;

    // Xác định các tên không yêu cầu `comment_threshold`
    $exemptedNames = ['Admin', 'Mod', 'Đội Sub'];
    $name = $this->crud->getRequest()->input('name');
    $commentThreshold = $this->crud->getRequest()->input('comment_threshold');

    $rules = [];

    // Nếu `name` không thuộc các giá trị ngoại lệ, thêm quy tắc `comment_threshold`
    
    // if (in_array($name, $exemptedNames)) {
    //     if (!empty($commentThreshold)) {  // Nếu `comment_threshold` không trống
    //             // $fail('Không được điền mục tiêu khi tên là Admin, Mod hoặc Đội Sub.');
    //             $rules['comment_threshold'] = [
    //                 function ($attribute, $value, $fail) {
    //                     $fail('Không được điền mục tiêu khi tên là Admin, Mod hoặc Đội Sub.');
    //                 },
    //             ];
    //     }
    // } else {
    //     // Nếu tên không thuộc các ngoại lệ, áp dụng các quy tắc cho `comment_threshold`
    //     $maxCommentThreshold = Badge::max('comment_threshold');
    //     $rules['comment_threshold'] = [
    //         'required',
    //         'numeric',
    //         'gt:0',
    //         function ($attribute, $value, $fail) use ($maxCommentThreshold) {
    //             if ($value < 2 * $maxCommentThreshold) {
    //                 $requiredValue = 2 * $maxCommentThreshold;
    //                 $fail("Mục tiêu ít nhất phải gấp đôi so với mục tiêu của huy hiệu trước đó. (>= {$requiredValue})");
    //             }
    //         },
    //     ];
    // }

    // $messages = [
    //     'comment_threshold.gt' => 'Mục tiêu phải là số nguyên lớn hơn 0.',
    //     'comment_threshold.required' => 'Mục tiêu là bắt buộc, trừ khi tên là Admin, Mod hoặc Đội Sub.',
    // ];
    // $this->crud->setValidation($rules, $messages);

    return $this->backpackUpdate();
}

    
    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }


    public function destroy($id)
    {
        $badge = Badge::find($id);
        return CRUD::delete($id);
    }
}
