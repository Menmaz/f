<?php

namespace App\Http\Resources;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Ophim\Core\Models\CommentReaction;

class CommentResource extends JsonResource
{

    public function __construct($resource)
    {
        parent::__construct($resource);
    }

    public function toArray($request)
    {

        $sortedReplies = $this->replies()->orderByDesc('created_at')->get();
        $reactionStatus = $this->getReactionStatus($this->id);

        return [
            'id' => $this->id ?? '',
            'user' => new UserResource($this->user),
            'isLiked' => $reactionStatus['isLiked'],
            'isDisliked' => $reactionStatus['isDisliked'],
            'totalLikes' => $this->getTotalLikes(),
            'totalDisLikes' => $this->getTotalDislikes(),
            'content' => $this->content ?? '',
            'createdAt' => DateHelper::localizeDate($this->created_at),
            'updatedAt' => DateHelper::localizeDate($this->updated_at),
            'replies' => $this->convertReplies($sortedReplies),
        ];
    
    }

    protected function getReactionStatus($comment_id)
    {
        $user = auth()->user();
        if ($user === null) {
            return ['isLiked' => false, 'isDisliked' => false];
        }

        $user_id = $user->id;

        $reactions = CommentReaction::where('user_id', $user_id)
            ->where('comment_id', $comment_id)
            ->get()
            ->groupBy('type');

        return [
            'isLiked' => isset($reactions[1]),
            'isDisliked' => isset($reactions[0]),
        ];
    }


    //ĐỆ QUY
//     protected function convertReplies($replies)
// {
//     // The result array to hold the converted comments
//     $result = [];

//     if ($replies->isEmpty()) {
//         return $result;
//     }

//     // Start processing the top-level replies
//     $this->processReplies($replies, $result);

//     return $result;
// }

// // Recursive function to traverse and convert replies
// function processReplies($replies, &$result, $level = 0) {
//     foreach ($replies as $reply) {
//         // Convert the current reply and add it to the result
//         $result[] = $this->createCommentData($reply, $level);

//         // Get nested replies for the current reply
//         $nestedReplies = $reply->replies()->get();

//         if (!$nestedReplies->isEmpty()) {
//             // Recursively process nested replies
//             $this->processReplies($nestedReplies, $result, $level + 1);
//         }
//     }
// }

protected function convertReplies($replies)
{
    // The result array to hold the converted comments
    $result = [];

    if ($replies->isEmpty()) {
        return $result;
    }

    // Create a stack for iterative processing
    $stack = [];

    // Initialize the stack with the top-level replies
    foreach ($replies as $reply) {
        $stack[] = ['reply' => $reply, 'level' => 0];
    }

    while (!empty($stack)) {
        // Pop the top item from the stack
        $current = array_pop($stack);
        $reply = $current['reply'];
        $level = $current['level'];

        // Convert the current reply and add it to the result
        $result[] = $this->createCommentData($reply, $level);

        // Get nested replies and add them to the stack
        $nestedReplies = $reply->replies()->get();
        foreach ($nestedReplies as $nestedReply) {
            $stack[] = ['reply' => $nestedReply, 'level' => $level + 1];
        }
    }

    return $result;
}
    
    protected function createCommentData($comment)
{
    $user = $comment->user;
    $parent = $comment->parent;

    // Thông tin về người dùng của bình luận cha (parentUser)
    $parentUser = $parent ? $parent->user : null;

    $reactionStatus = $this->getReactionStatus($comment->id);
    
    // Thông tin về bình luận và người dùng
    return [
        'id' => $comment->id ?? '',
        'user' => new UserResource($user),
        'parentUser' => new UserResource($parentUser),
        'isLiked' => $reactionStatus['isLiked'],
        'isDisliked' => $reactionStatus['isDisliked'],
        'totalLikes' => $comment->getTotalLikes(),
        'totalDisLikes' => $comment->getTotalDislikes(),
        'content' => $comment->content ?? '',
        'createdAt' => DateHelper::localizeDate($comment->created_at),
        'updatedAt' => DateHelper::localizeDate($comment->updated_at),
    ];
}


}
