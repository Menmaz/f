<div>

@if (session()->has('message'))
            <script>
                toastr.error("{{ session('message') }}")
            </script>
@elseif(session()->has('success_message'))
            <script>
                toastr.success("{{ session('success_message') }}")
            </script>
@endif

@php
    $userId = auth()->id();
@endphp
    
        <div style="display: flex; padding: 10px; width: 100%;">
            <img width="48" height="48" style="border-radius: 12px;" src="
            @auth {{ auth()->user()->avatar ?? asset('frontend-web/images/user-default-avatar.jpg') }} @else {{ asset('frontend-web/images/user-default-avatar.jpg') }} @endauth">
            <form style="flex: 1; margin-left: 10px;" wire:submit.prevent="addComment">
                <textarea class="form-control" style="width: 100%; height: 50px; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" wire:model="newComment" placeholder="Nhập bình luận của bạn"></textarea>
                <div style="display: flex; justify-content: flex-end;">
                    <button wire:loading wire:target="addComment" type="submit" class="btn btn-primary rounded-pill" style="padding: 8px 18px;margin-top: 10px;">Bình luận</button>
                </div>
            </form>
        </div>

        @foreach($comments as $comment)
        <div style="display: flex; padding: 10px; width: 100%;">
            <img width="48" height="48" style="border-radius: 12px;" src="{{ $comment->user->avatar ?? asset('frontend-web/images/user-default-avatar.jpg') }}" alt="Avatar">
            <div style="display: flex; flex-direction: column; justify-content: space-between; margin-left: 10px; width: calc(100% - 62px)">
                <div style="display: flex;">
                    <span style="color: white; font-weight: bold;">{{ $comment->user->username }}</span>
                    @if($comment->user->is_admin)
                    <span style="background-color: #000; margin-left: 5px; padding: 5px; color: white; border-radius: 4px;margin-top: -3px;">Admin</span>
                    @endif
                    <span style="color: grey; margin-left: 10px;">{{ \Carbon\Carbon::parse($comment->created_at)->diffForHumans() }}</span>
                </div>
                <div style="color: white; margin-top: 5px;">
                    {{ $comment->content }}
                </div>
                <div style="display: flex; align-items: center; margin-top: 10px;">
                    @php
                        $userReaction = $comment->reactions->where('user_id', $userId)->first();
                        $isLiked = $userReaction && $userReaction->type === 0;
                        $isDisliked = $userReaction && $userReaction->type === 1;
                    @endphp
                    <button style="background-color: transparent; border: none; margin-right: 10px;" wire:click="likeComment({{ $comment->id }})">
                        <i class="fas fa-thumbs-up {{ $isLiked ? 'fa-solid' : 'fa-regular' }}" style="color: white;"></i>
                    </button>
                    <button style="background-color: transparent; border: none; margin-right: 10px;" wire:click="dislikeComment({{ $comment->id }})">
                        <i class="fas fa-thumbs-down {{ $isDisliked ? 'fa-solid' : 'fa-regular' }}" style="color: white;"></i>
                    </button>
                    <a href="javascript:void(0);" wire:click="showReplyForm({{ $comment->id }})">Trả lời</a>
                    @if($comment->user->id == $userId || optional(auth()->user())->is_admin)
                    <a href="javascript:void(0);" style="margin-left: 10px;" wire:click="showEditForm({{ $comment->id }})">Sửa</a>
                    <a href="javascript:void(0);" style="margin-left: 10px;" wire:click="deleteComment({{ $comment->id }})">Xóa</a>
                    @endif
                </div>

                @if($commentIdBeingReplied == $comment->id)
                <div class="reply-form" style="margin-top: 10px;">
                    <textarea class="form-control" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" wire:model="replyComment" placeholder="Nhập phản hồi của bạn">
                    </textarea>
                    <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
                        <button wire:loading wire:target="replyToComment" type="submit" class="btn btn-primary rounded-pill" wire:click="replyToComment({{ $comment->id }})" style="padding: 6px 16px;">Trả lời</button>
                    </div>
                </div>
                @endif

                @if($commentIdBeingEdited == $comment->id)
                <div class="edit-form" style="margin-top: 10px;">
                    <textarea class="form-control" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" wire:model="editComment" placeholder="Nội dung sửa bình luận">
                    </textarea>
                    <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
                        <button wire:loading wire:target="replyToComment" type="submit" class="btn btn-primary rounded-pill" wire:click="editToComment({{ $comment->id }})" style="padding: 6px 16px;">Lưu thay đổi</button>
                    </div>
                </div>
                @endif

                @foreach($comment->replies as $reply)
                <div style="border-left: 2px solid #ccc;margin-top: 8px;">
                    <div style="display: flex; padding: 8px; margin-bottom: 5px;">
                        <img width="48" height="48" style="border-radius: 12px" src="{{ $comment->user->avatar ?? asset('frontend-web/images/user-default-avatar.jpg') }}" alt="Avatar">
                        <div style="display: flex; flex-direction: column; justify-content: space-between; margin-left: 10px; width: calc(100% - 52px)">
                            <div style="display: flex; align-items: center;">
                                <span style="color: white; font-weight: bold;">{{ $reply->user->username }}</span>
                                @if($reply->user->is_admin)
                                <span style="background-color: #000; margin-left: 5px; padding: 5px; color: white; border-radius: 4px;margin-top: -3px;">Admin</span>
                                @endif
                                <span style="color: grey; margin-left: 10px;">{{ \Carbon\Carbon::parse($reply->created_at)->diffForHumans() }}</span>
                            </div>
                            <div style="color: white; margin-top: 3px;">
                                <span style="color: #3c8bc6">{{ $comment->user->username }}</span> {{ $reply->content }}
                            </div>
                            <div style="display: flex; align-items: center; margin-top: 5px;">
                                @php
                                    $userReactionReply = $reply->reactions->where('user_id', $userId)->first();
                                    $isLikedReply = $userReactionReply && $userReactionReply->type === 0;
                                    $isDislikedReply = $userReactionReply && $userReactionReply->type === 1;
                                @endphp
                                <button style="background-color: transparent; border: none; margin-right: 10px;" wire:click="likeComment({{ $reply->id }})">
                                    <i class="fas fa-thumbs-up {{ $isLikedReply ? 'fa-solid' : 'fa-regular' }}" style="color: white;"></i>
                                </button>
                                <button style="background-color: transparent; border: none; margin-right: 10px;" wire:click="dislikeComment({{ $reply->id }})">
                                    <i class="fas fa-thumbs-down {{ $isDislikedReply ? 'fa-solid' : 'fa-regular' }}" style="color: white;"></i>
                                </button>
                                @if($reply->user->id == $userId || optional(auth()->user())->is_admin)
                                <a href="javascript:void(0);" style="margin-right: 10px;" wire:click="showEditForm({{ $reply->id }})">Sửa</a>
                                <a href="javascript:void(0);" wire:click="deleteComment({{ $comment->id }})">Xóa</a>
                                @endif
                            </div>

                            @if($commentIdBeingReplied == $reply->id)
                            <div class="reply-form" style="margin-top: 10px;">
                                <textarea class="form-control" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" wire:model="replyComment" placeholder="Nhập phản hồi của bạn"></textarea>
                                <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
                                    <button type="submit" class="btn btn-primary rounded-pill" wire:click="replyToComment({{ $reply->id }})" style="padding: 6px 16px;">Gửi</button>
                                </div>
                            </div>
                            @endif

                            @if($commentIdBeingEdited == $reply->id)
                            <div class="edit-form" style="margin-top: 10px;">
                                <textarea class="form-control" style="width: 100%; height: 60px; padding: 10px; border-radius: 8px; border: 1px solid #ccc;" wire:model="editComment" placeholder="Nội dung sửa bình luận">
                                </textarea>
                                <div style="display: flex; justify-content: flex-end; margin-top: 5px;">
                                    <button wire:loading wire:target="replyToComment" type="submit" class="btn btn-primary rounded-pill" wire:click="editToComment({{ $reply->id }})" style="padding: 6px 16px;">Lưu thay đổi</button>
                                </div>
                            </div>
                            @endif

                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach

    @if ($comments->count() >= $perPage)
    <nav class="mt-4 d-flex justify-content-center p-3">
        <button class="btn btn-lg btn-primary" wire:loading.attr="disabled" wire:click="previousPage" @if ($page === 1) disabled @endif>
            <i class="fa-regular fa-chevron-left"></i> Trước
        </button>
        <button class="btn btn-lg btn-primary" wire:loading.attr="disabled" wire:click="nextPage" style="margin-left: 5px;" @if ($comments->count() < $perPage) disabled @endif>
            Sau <i class="fa-regular fa-chevron-right"></i>
        </button>
    </nav>
    @endif

    </div>