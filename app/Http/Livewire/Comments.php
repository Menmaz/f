<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Ophim\Core\Models\Comment;
use Ophim\Core\Models\CommentReaction;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Notification;

class Comments extends Component
{
    public $manga;
    public $newComment;
    public $editComment;
    public $replyComment;
    public $commentIdBeingReplied;
    public $commentIdBeingEdited;
    public $comments;
    public $page = 1;
    public $perPage = 5;

    public function mount(Manga $manga)
    {
        $this->manga = $manga;
        $this->loadComments();
    }

    private function loadComments()
    {
        $offset = ($this->page - 1) * $this->perPage;
        $this->comments = $this->manga->comments()
        ->with([
            'user', 
            'reactions', 
            'replies',
            'replies.user',
            'replies.reactions'
        ])
        ->orderBy('created_at', 'desc')
        ->skip($offset)
        ->take($this->perPage)
        ->get();
    }

    public function nextPage()
    {
        $this->page++;
        $this->loadComments();
    }

    public function previousPage()
    {
        if ($this->page > 1) {
            $this->page--;
        }
        $this->loadComments();
    }

    private function ensureAuthenticated()
    {
        if (!Auth::check()) {
            session()->flash('message', 'Bạn phải đăng nhập để tiếp tục.');
            return false;
        }
        return true;
    }

    public function addComment()
    {
        if (!$this->ensureAuthenticated()) return;
        $this->manga->comments()->create([
            'content' => $this->newComment, 
            'user_id' => Auth::id(),
            'commentable_type' => Manga::class,
        ]);
        $this->page = 1;
        $this->loadComments();
        $this->newComment = '';
    }

    public function editToComment($id)
    {
        if (!$this->ensureAuthenticated()) return;
        $comment = Comment::find($id);
        $comment->content = $this->editComment;
        $comment->save();
        $this->editComment = '';
        $this->loadComments();
        $this->commentIdBeingEdited = null;
        session()->flash('success_message', 'Sửa bình luận thành công !.');
    }

    public function replyToComment($id)
    {
        if (!$this->ensureAuthenticated()) return;

        $user_id = auth()->id();

        $comment = Comment::create([
        'content' => $this->replyComment,
        'commentable_id' => $this->manga->id, 
        'parent_id' => $id, 
        'user_id' => $user_id,
        'commentable_type' => Manga::class,
        ]);

        // Tạo thông báo cho người đã đăng bình luận gốc;
        $parentComment = Comment::with('user')->find($id);
        if ($parentComment && $parentComment->user_id != $user_id) {
            $parentCommentOwner = $parentComment->user;
            
            // Tìm kiếm thông báo đã tồn tại
            $existingNotification = $parentCommentOwner->notifications()
            ->where('message', auth()->user()->username . " đã trả lời bình luận của bạn")
            ->where('manga_id', $this->manga->id)
            ->where('comment_id', $comment->id)
            ->where('related_user_id', $user_id)
            ->where('type', 'reply')
            ->first();

            if ($existingNotification) {
                $existingNotification->update(['created_at' => now()]);
            } else {
                $parentCommentOwner->notifications()->create([
                    'message' => auth()->user()->username . " đã trả lời bình luận của bạn",
                    'type' => 'reply',
                    'manga_id' => $this->manga->id,
                    'comment_id' => $comment->id,
                    'related_user_id' => $user_id
                ]);
            }
        }

        $this->replyComment = '';
        $this->loadComments();
        $this->commentIdBeingReplied = null;
    }

    public function likeComment($id)
    {
        if (!$this->ensureAuthenticated()) return;

        $user_id = auth()->id();

        $existingReaction = CommentReaction::where('user_id', $user_id)
                                        ->where('comment_id', $id)
                                        ->where('type', 0)
                                        ->first();

        if ($existingReaction) {
            $existingReaction->delete();

            // Delete existing notification for unliking the comment
            $comment = Comment::find($id);
            $commentOwner = $comment->user;
            $commentOwner->notifications()
                ->where('comment_id', $id)
                ->where('type', 'like')
                ->delete();

        } else {
            // If not liked, create a new like reaction and notification
            CommentReaction::updateOrCreate(
                ['user_id' => $user_id, 'comment_id' => $id],
                ['type' => 0]
            );

            // Notify the owner of the comment
            $comment = Comment::find($id);
            if ($comment && $comment->user_id != $user_id) {
                $commentOwner = $comment->user;
                $commentOwner->notifications()
                ->where('comment_id', $id)
                ->where('type', 'dislike')
                ->delete();
                
                $commentOwner->notifications()->create([
                    'message' => auth()->user()->username . " đã thích bình luận của bạn.",
                    'type' => 'like',
                    'manga_id' => $comment->commentable_id, // Assuming Manga ID is needed
                    'comment_id' => $id,
                    'related_user_id' => $user_id
                ]);
            }
        }

        $this->loadComments();
    }

    public function dislikeComment($id)
    {
        if (!$this->ensureAuthenticated()) return;

        $user_id = auth()->id();

        $existingReaction = CommentReaction::where('user_id', $user_id)
                                        ->where('comment_id', $id)
                                        ->where('type', 1)
                                        ->first();

        if ($existingReaction) {
            $existingReaction->delete();

            // Delete existing notification for unliking the comment
            $comment = Comment::find($id);
            $commentOwner = $comment->user;
            $commentOwner->notifications()
                ->where('comment_id', $id)
                ->where('type', 'like')
                ->where('type', 'dislike')
                ->delete();

        } else {
            CommentReaction::updateOrCreate(
                ['user_id' => $user_id, 'comment_id' => $id],
                ['type' => 1]
            );

            // Notify the owner of the comment
            $comment = Comment::find($id);
            if ($comment && $comment->user_id != $user_id) {
                $commentOwner = $comment->user;
                $commentOwner->notifications()
                ->where('comment_id', $id)
                ->where('type', 'like')
                ->delete();
                
                $commentOwner->notifications()->create([
                    'message' => auth()->user()->username . " đã không thích bình luận của bạn.",
                    'type' => 'dislike',
                    'manga_id' => $comment->commentable_id, // Assuming Manga ID is needed
                    'comment_id' => $id,
                    'related_user_id' => $user_id
                ]);
            }
        }

        $this->loadComments();
    }

    public function deleteComment($id)
    {
        if (!$this->ensureAuthenticated()) return;
        
        $comment = Comment::find($id);
        
        if ($comment && $comment->user_id === Auth::id()) {
            $comment->delete();

             // Xóa các thông báo liên quan
            Notification::where('comment_id', $id)->delete();

            // Xóa các phản ứng (reaction) liên quan
            CommentReaction::where('comment_id', $id)->delete();
        } else {
            session()->flash('message', 'Bạn không có quyền xóa bình luận này.');
        }

        $this->loadComments();
    }

    public function showEditForm($id)
    {
        if (!$this->ensureAuthenticated()) return;
        $this->commentIdBeingReplied = null;
        $this->commentIdBeingEdited = $this->commentIdBeingEdited === $id ? null : $id;
    }

    public function showReplyForm($id)
    {
        if (!$this->ensureAuthenticated()) return;
        $this->commentIdBeingEdited = null;
        $this->commentIdBeingReplied = $this->commentIdBeingReplied === $id ? null : $id;
    }

    public function render()
    {
        return view('frontend-web.manga-detail.components.comments');
    }
}

