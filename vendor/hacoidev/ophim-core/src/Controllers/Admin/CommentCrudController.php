<?php

namespace Ophim\Core\Controllers\Admin;

use App\Helpers\DateHelper;
use Ophim\Core\Requests\MovieRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Ophim\Core\Helpers\UserHelper;
use Ophim\Core\Models\Comment;
use Ophim\Core\Models\CommentReaction;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class MovieCrudController
 * @package Ophim\Core\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CommentCrudController extends CrudController
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
        CRUD::setModel(\Ophim\Core\Models\Comment::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/comment');
        CRUD::setEntityNameStrings('Bình luận', 'Bình luận');
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
        $websiteUrl = "https://trieubui.top/truyen-tranh/";
        CRUD::addColumn([
            'name' => 'manga_title',
            'label' => 'Thông tin',
            'type' => 'custom_html',
            'value' => function($entry) {
                $manga = $entry->manga;
                $cleanContent = strip_tags($entry->content);
                return '
                <div class="card text-white bg-primary mb-3" style=" white-space: normal;text-align:center">
                <div class="card-header">
                <b>Truyện</b>: <a href="' .route('manga.detail', ['slug' => $manga->slug ]). '" target="_blank" class="card-title text-white">'.$manga->title.'</a>
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
        

        // CRUD::addColumn(['name' => 'total_likes', 'label' => 'Lượt likes', 'type' => 'number', 'value' => function($row) {
        //     return $row->getTotalLikes();
        // }]);
        // CRUD::addColumn(['name' => 'total_dislikes', 'label' => 'Lượt dislikes', 'type' => 'number', 'value' => function($row) {
        //     return $row->getTotalDislikes();
        // }]);
        // CRUD::addColumn(['name' => 'total_replies', 'label' => 'Lượt phản hồi', 'type' => 'number', 'value' => function($row) {
        //     return $row->getTotalReplies();
        // }]);
        // CRUD::addColumn(['name' => 'updated_at', 'label' => 'Ngày bình luận', 'type' => 'datetime',]);
    }

 
    public function destroy($id)
    {
        $id = $this->crud->getCurrentEntryId() ?? $id;

        $res = $this->crud->delete($id);
        if ($res) {
            $childComments = Comment::where('parent_id', $id)->get();
            $commentReactions = CommentReaction::where('comment_id', $id)->get();
        foreach($childComments as $childComment){
            $childComment->delete();
        }
        foreach($commentReactions as $commentReaction){
            $commentReaction->delete();
        }
        }
        

        return $res;
    }

}
