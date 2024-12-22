<div style="display: flex; padding: 10px; border-radius: 12px; margin-bottom: 5px;">
 <span style="color: white; font-weight: bold; ">
    {{ $comment->user->username }}</span>
     @if($comment->user->is_admin) 
    <span style="background-color: #000; margin-left: 5px; padding: 5px; color: white; border-radius: 4px;margin-top: -3px;">Admin</span> 
    @endif
</div>
<div style="color: white; margin-top: 5px;">
 {{ $comment->content }}
</div>
<div style="display: flex; align-items: center; margin-top: 10px;"> @php $userReaction = $comment->reactions->where('user_id', $userId)->first(); $isLiked = $userReaction && $userReaction->type === 0; $isDisliked = $userReaction && $userReaction->type === 1; @endphp <button style="background-color: transparent; border: none; margin-right: 10px;" wire:click="likeComment({{ $comment->id }})">
  <i class="fas fa-thumbs-up {{ $isLiked ? 'fa-solid' : 'fa-regular' }}" style="color: white;"></i>
 </button>
 <button style="background-color: transparent; border: none; margin-right: 10px;" wire:click="dislikeComment({{ $comment->id }})">
  <i class="fas fa-thumbs-down {{ $isDisliked ? 'fa-solid' : 'fa-regular' }}" style="color: white;"></i>
 </button>
 <a href="javascript:void(0);" style="margin-right: 10px;" wire:click="showEditForm({{ $comment->id }})">Sửa</a>
 <a href="javascript:void(0);" wire:click="showReplyForm({{ $comment->id }})">Trả lời</a>
</div> 
@if($commentIdBeingReplied == $comment->id) 
<div class="reply-form" style="margin-top: 10px;">
 <textarea class="form-control" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" wire:model="replyComment" placeholder="Nhập phản hồi của bạn"></textarea>
 <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
  <button wire:loading wire:target="replyToComment" type="submit" class="btn btn-primary rounded-pill" wire:click="replyToComment({{ $comment->id }})" style="padding: 6px 16px;">Trả lời</button>
 </div>
</div> 
@endif 
@if($commentIdBeingEdited == $comment->id) <div class="edit-form" style="margin-top: 10px;">
 <textarea class="form-control" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" wire:model="editComment" placeholder="Nội dung sửa bình luận"></textarea>
 <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
  <button wire:loading wire:target="replyToComment" type="submit" class="btn btn-primary rounded-pill" wire:click="editToComment({{ $comment->id }})" style="padding: 6px 16px;">Lưu thay đổi</button>
 </div>
</div>
 @endif