@extends($themeLayout)

@section('content')

@php
if(!empty($document->user_id)){
    $myCharacter = Helper::getMyMainChr($document->user_id, $group->id);
}
@endphp

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush
<div class="max-w-5xl mx-auto px-4 py-8 relative">
    @if($board->notice != null)
    <div class="space-y-8 mb-8">
        <div class="px-5 py-4 flex justify-between items-center border border-gray-100 text-center">
            <div class="w-full text-center">
                {!! $board->notice !!}
            </div>
        </div>
    </div>
    @endif
    <div class="space-y-4">
        @if(!$document->is_secret || $_SESSION['level'] === 10 || $document->user_id === $_SESSION['user_idx'])
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-3 w-full">
                    
                    <div class="flex-shrink-0 w-10 overflow-hidden h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                        @if (!empty($myCharacter))
                            <img src="{{ $myCharacter->image_path }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        @endif
                    </div>
                    
                    <div class="w-full">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-gray-800 text-sm">{{ $document->nickname }} {{ $document->is_secret ? '🔒' : '' }}</span>
                            <span class="text-xs text-gray-400">{{ date('Y.m.d H:i', strtotime($document->created_at)) }}</span>
                        </div>
                        <p class="text-gray-700 text-sm leading-relaxed mb-2">
                            {!! nl2br(e($document->content)) !!}
                        </p>
                        
                        <div class="flex items-center space-x-3">
                            @if(($_SESSION['level'] ?? 0) >= $board->comment_level)
                            <button onclick="toggleReplyForm({{ $document->id }})" class="text-xs text-indigo-600 font-semibold hover:underline flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                답글 달기
                            </button>
                            @endif
                            @if(($_SESSION['user_idx'] ?? 0) == $document->user_id || $_SESSION['level'] === 10)
                            <form action="{{ $currentUrl }}/{{ $document->doc_num }}/delete" method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                <button class="text-xs text-red-400 hover:text-red-600 hover:underline">삭제</button>
                            </form>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            @if($document->comment_count > 0)
            @foreach($comments as $cmt)
                <div class="mt-4 ml-12 bg-indigo-50 rounded-lg p-4 relative border-l-4 border-indigo-200">
                    <div class="flex items-start">
                        <div class="mr-3 mt-1 text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-bold text-gray-800 text-sm">{{ $cmt->nickname }}</span>
                                <span class="text-xs text-gray-400">{{ date('m.d H:i', strtotime($cmt->created_at)) }}</span>
                                @if(($_SESSION['user_idx'] ?? 0) == $document->user_id || $_SESSION['level'] === 10)
                                <form action="{{ $base_path }}/comment/delete" method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="comment_id" value="{{ $cmt->id }}">
                                    <input type="hidden" name="doc_id" value="{{ $document->id }}">
                                    <button class="text-xs text-red-400">삭제</button>
                                </form>
                                @endif
                            </div>
                            <p class="text-gray-700 text-sm">
                                {!! nl2br($cmt->content) !!}
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
            @endif
            @if(($_SESSION['level'] ?? 0) >= $board->comment_level)
            <div id="reply-form-{{ $document->id }}" class="hidden mt-4 ml-12 animate-fade-in-down">
                <form action="{{ $currentUrl }}/comment" method="POST" class="flex items-start space-x-2">
                    <textarea name="content" class="w-full h-20 p-3 bg-white border border-indigo-200 rounded focus:outline-none focus:border-indigo-500 text-sm resize-none" placeholder="답글 내용을 입력하세요..."></textarea>
                    <button type="submit" class="h-20 w-16 bg-indigo-600 text-white rounded hover:bg-indigo-700 text-sm font-bold flex flex-col items-center justify-center">
                        <span>등록</span>
                    </button>
                </form>
            </div>
            @endif
        </div>
        @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-3 w-full">
                    <div class="w-full text-center">
                        비밀글입니다.
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

</div>

<script>
    function toggleReplyForm(id) {
        const form = document.getElementById('reply-form-' + id);
        if (form.classList.contains('hidden')) {
            form.classList.remove('hidden');
        } else {
            form.classList.add('hidden');
        }
    }
</script>
@endsection