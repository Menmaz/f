<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Ophim\Core\Models\Comment;

class NotificationResource extends JsonResource
{

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request)
    {
        // $status = ($this->status == 1) ? 'da-xem' : 'chua-xem';
        $manga = optional($this->manga);
        // $comment = optional($this->comment);

        return [
            'id' => $this->id,
            'message' => $this->message ?? '',
            'status' => boolval($this->status ?? false),
            'type' => $this->type ?? '',
            'manga' => [
                'title' => $manga->title ?? '',
                'slug' => $manga->slug ?? '',
                'thumb_url' => $manga->cover ?? '',
                'href' => ("truyen/" .$manga->slug) ?? ''
            ],
            'repliedCommentContent' => $this->getRepliedCommentContent(),
            'repliedUser' => $this->getRepliedUser(),
            'createdAt' => DateHelper::localizeDate($this->created_at),
        ];
    
    }

    // public function getRepliedCommentContent(){
    //     $comment = Comment::where('id', $this->comment_id)->first(['content']);
    //     if(!$comment){
    //         return '';
    //     }
    //     return $comment->content;
    // }

    // public function getRepliedUser(){
    //     $replier = User::where('id', $this->related_user_id)->first();
    //     if(!$replier){
    //         return '';
    //     }
    //     return new UserResource($replier);
    // }

    public function getRepliedCommentContent() {
        return optional($this->comment)->content ?? '';
    }
    
    public function getRepliedUser() {
        return new UserResource($this->relatedUser) ?? [];
    }

}
