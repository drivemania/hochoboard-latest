@extends($themeLayout)

@section('content')

@php
$searchTarget = $_GET['search_target'] ?? "";
$keyword = $_GET['keyword'] ?? "";
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
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-8">
        <h2 class="text-lg font-bold text-gray-800 mb-3 flex items-center">
            {{ $board->title }}
        </h2>
        <form action="{{ $currentUrl }}/write" method="POST" class="relative">
            <input type="hidden" name="subject" value="방명록">
            <textarea name="content" 
                      class="w-full h-24 p-4 bg-gray-50 border border-gray-200 rounded-lg resize-none focus:outline-none focus:bg-white focus:border-indigo-500 transition text-sm"
                      placeholder="내용을 입력해주세요..."></textarea>
            
            <div class="flex justify-between items-center mt-2">
                @if($board->use_secret > 0)
                <label class="flex items-center space-x-2 cursor-pointer text-sm text-gray-600 select-none">
                    <input type="checkbox" name="is_secret" class="form-checkbox h-4 w-4 text-indigo-600 rounded border-gray-300">
                    <span>🔒 비밀글로 남기기</span>
                </label>
                @endif
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                    등록하기
                </button>
            </div>
        </form>
    </div>

    <div class="space-y-4">
        @forelse($documents as $doc)
        @if(!$doc->is_secret || $_SESSION['level'] === 10 || $doc->user_id === $_SESSION['user_idx'])
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex items-start space-x-3 w-full">
                    
                    <div class="flex-shrink-0 w-10 overflow-hidden h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500">
                        @if (isset($doc->char_image))
                            <img src="{{ $doc->char_image }}" class="w-full h-full object-cover">
                        @else
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        @endif
                    </div>
                    
                    <div class="w-full">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-bold text-gray-800 text-sm">{{ $doc->nickname }} {{ $doc->is_secret ? '🔒' : '' }}</span>
                            <span class="text-xs text-gray-400">{{ date('Y.m.d H:i', strtotime($doc->created_at)) }}</span>
                        </div>
                        <p class="text-gray-700 text-sm leading-relaxed mb-2">
                            {!! nl2br(e($doc->content)) !!}
                        </p>
                        
                        <div class="flex items-center space-x-3">
                            @if(($_SESSION['level'] ?? 0) >= $board->comment_level)
                            <button onclick="toggleReplyForm({{ $doc->id }})" class="text-xs text-indigo-600 font-semibold hover:underline flex items-center">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                답글 달기
                            </button>
                            @endif
                            @if(($_SESSION['user_idx'] ?? 0) == $doc->user_id || $_SESSION['level'] === 10)
                            <form action="{{ $currentUrl }}/{{ $doc->doc_num }}/delete" method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                <button class="text-xs text-red-400 hover:text-red-600 hover:underline">삭제</button>
                            </form>
                            @endif
                        </div>
                    </div>

                </div>
            </div>

            @if(isset($doc->comments) && count($doc->comments) > 0)
            @foreach($doc->comments as $cmt)
                <div class="mt-4 ml-12 bg-indigo-50 rounded-lg p-4 relative border-l-4 border-indigo-200">
                    <div class="flex items-start">
                        <div class="mr-3 mt-1 text-indigo-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                        </div>
                        <div>
                            <div class="flex items-center space-x-2 mb-1">
                                <span class="font-bold text-gray-800 text-sm">{{ $cmt->nickname }}</span>
                                <span class="text-xs text-gray-400">{{ date('m.d H:i', strtotime($cmt->created_at)) }}</span>
                                @if(($_SESSION['user_idx'] ?? 0) == $doc->user_id || $_SESSION['level'] === 10)
                                <form action="{{ $base_path }}/comment/delete" method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="comment_id" value="{{ $cmt->id }}">
                                    <input type="hidden" name="doc_id" value="{{ $doc->id }}">
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
            @else
            @endif
            @if(($_SESSION['level'] ?? 0) >= $board->comment_level)
            <div id="reply-form-{{ $doc->id }}" class="hidden mt-4 ml-12 animate-fade-in-down">
                <form action="{{ $currentUrl }}/{{ $doc->doc_num }}/comment" method="POST" class="flex items-start space-x-2">
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
        @empty
        <div class="text-center py-20 bg-gray-50 rounded-2xl border border-gray-100">
            <p class="text-gray-500 font-medium">등록된 글이 없습니다.</p>
        </div>
        @endforelse
    </div>

    @if($documents->lastPage() > 1)
    <div class="mt-6 flex justify-center space-x-1">
        @for($i = 1; $i <= $documents->lastPage(); $i++)
            <a href="?page={{ $i }}" 
               class="px-3 py-1 rounded border {{ $documents->currentPage() == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
               {{ $i }}
            </a>
        @endfor
    </div>
    @endif
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