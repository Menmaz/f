<?php

namespace Tannhatcms\Notifications\Controllers;


use Backpack\CRUD\app\Http\Controllers\CrudController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Support\Facades\Route;
use Tannhatcms\Notifications\Option;
use Prologue\Alerts\Facades\Alert;
/**
 * Class NotificationsCrudController
 * @package Tannhatcms\Notifications\Controllers
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class NotificationsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Setting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/plugin/notifications');
        CRUD::setEntityNameStrings('Cài đặt thông báo', 'Cài đặt thông báo');
    }

       /**
     * Define which routes are needed for this operation.
     *
     * @param  string  $name  Name of the current entity (singular). Used as first URL segment.
     * @param  string  $routeName  Prefix of the route name.
     * @param  string  $controller  Name of the current CrudController.
     */
    protected function setupUpdateRoutes($segment, $routeName, $controller)
    {
        Route::get($segment , [
            'as'        => $routeName . '.editNotifications',
            'uses'      => $controller . '@editNotifications',
            'operation' => 'updateNotifications',
        ]);
        Route::put($segment , [
            'as'        => $routeName . '.updateNotifications',
            'uses'      => $controller . '@updateNotifications',
            'operation' => 'updateNotifications',
        ]);
    }

    public function editNotifications(){
        $this->crud->hasAccessOrFail('update');
        $this->data['entry'] = Setting::where('key', "notifications")->first();
        if (is_null($this->data['entry'])) {
            Alert::error("Xảy ra lỗi hệ thống, không tìm thấy notifications, vui lòng cài đặt lại.")->flash();
            return back();
        }
        $field = array_merge(json_decode($this->data['entry']['field'], true), ['value' => $this->data['entry']['value']]);
        $this->crud->setOperationSetting('fields', [$field]);
        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        return view('notification::setting', $this->data);
    }

    /**
     * Update the specified resource in the database.
     *
     * @return array|\Illuminate\Http\RedirectResponse
     */
    public function updateNotifications()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();
        Setting::where('key', "notifications")->update([
            'value' => $request["value"]
        ]);
        Alert::success(trans('backpack::crud.update_success'))->flash();
        return back();
    }

}