@extends($themeLayout)

@section('content')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@php

if(!empty($document->content)){
    $document->content = str_replace('<ol>', '<ol class="list-decimal">', $document->content);
    $document->content = str_replace('<ul>', '<ul class="list-disc">', $document->content);
}

@endphp

<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    
    <div class="border-b pb-4 mb-6">
        <span class="text-blue-600 font-bold text-sm">{{ $board->title }}</span>
        <h1 class="text-2xl font-bold text-gray-800 mt-1">{{ $document->title }}</h1>
        <div class="flex items-center text-sm text-gray-500 mt-2 space-x-4">
            <span>{{ $document->nickname }}</span>
            <span>{{ date('Y-m-d H:i', strtotime($document->created_at)) }}</span>
            <span>조회 {{ number_format($document->hit) }}</span>
        </div>
    </div>
    @php
        $customFields = $board->custom_fields ? json_decode($board->custom_fields, true) : [];
        $savedData = $document->custom_data ? json_decode($document->custom_data, true) : [];
    @endphp

    @if(!empty($customFields))
    <div class="bg-gray-50 p-4 rounded border mb-6 text-sm">
        <table class="w-full">
            @foreach($customFields as $field)
                @php $val = $savedData[$field['name']] ?? '-'; @endphp
                <tr class="border-b border-gray-200 last:border-0">
                    <th class="w-32 py-2 text-left text-gray-500 font-normal pl-2">{{ $field['name'] }}</th>
                    <td class="py-2 text-gray-800 font-bold">{{ $val }}</td>
                </tr>
            @endforeach
        </table>
    </div>
    @endif

    <div class="min-h-[200px] mb-10 prose max-w-none">
        @if($board->use_editor)
            {!! $document->content !!}
        @else
            {!! nl2br($document->content) !!}
        @endif
    </div>

    <div class="flex justify-end space-x-2 border-b pb-6 mb-6">
        @if( (isset($_SESSION['user_idx']) && $_SESSION['user_idx'] == $document->user_id) || ($_SESSION['level'] ?? 0) >= 10 )
            <a href="{{ $currentUrl }}/edit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm font-bold">수정</a>
            <form action="{{ $currentUrl }}/delete" method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                <button class="px-4 py-2 bg-gray-100 text-red-600 rounded hover:bg-gray-200 text-sm font-bold">삭제</button>
            </form>
        @endif
        <a href="{{ $listUrl }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm font-bold">목록</a>
    </div>

    <div class="bg-gray-50 p-4 rounded-lg">
        <h3 class="font-bold text-gray-700 mb-4">💬 댓글 ({{ $document->comment_count }})</h3>

        <ul class="space-y-4 mb-6">
            @foreach($comments as $cmt)
            <li x-data="{ editMode: false }" class="border-b border-gray-200 pb-2 last:border-0">
                
                <div class="flex justify-between items-center mb-1">
                    <span id="comment_{{ $cmt->id }}" class="scroll-mt-24 target:bg-yellow-50 font-bold text-sm text-gray-800">{{ $cmt->nickname }}</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-gray-400">{{ date('m.d H:i', strtotime($cmt->created_at)) }}</span>
                        
                        @if( (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $cmt->user_id) || ($_SESSION['level'] ?? 0) >= 10 )
                            
                            <button type="button" 
                                    x-show="!editMode" 
                                    @click="editMode = true" 
                                    class="text-xs text-gray-400 hover:text-blue-600">
                                수정
                            </button>

                            <form x-show="!editMode" action="{{ $base_path }}/comment/delete" method="POST" onsubmit="return confirm('댓글을 삭제할까요?');" class="inline">
                                <input type="hidden" name="comment_id" value="{{ $cmt->id }}">
                                <input type="hidden" name="doc_id" value="{{ $document->doc_num }}">
                                <button class="text-xs text-red-400 hover:text-red-600">삭제</button>
                            </form>
                        @endif
                    </div>
                </div>
                {!! $cmt->plugin ?? '' !!}
                <div x-show="!editMode" class="text-sm text-gray-700 whitespace-pre-wrap">{!! $cmt->content !!}</div>

                @if( (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $cmt->user_id) || ($_SESSION['level'] ?? 0) >= 10 )
                <div x-show="editMode" x-cloak class="mt-2">
                    <form action="{{ $base_path }}/comment/update" method="POST">
                        <input type="hidden" name="comment_id" value="{{ $cmt->id }}">
                        
                        <textarea name="content" class="w-full border rounded p-2 text-sm focus:ring-2 focus:ring-blue-200 resize-none h-20 mb-2" required>{{ $cmt->content }}</textarea>
                        
                        <div class="flex space-x-2">
                            <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-xs font-bold hover:bg-blue-700">저장</button>
                            <button type="button" @click="editMode = false" class="bg-gray-200 text-gray-600 px-3 py-1 rounded text-xs font-bold hover:bg-gray-300">취소</button>
                        </div>
                    </form>
                </div>
                @endif

            </li>
            @endforeach
            
            @if($comments->isEmpty())
                <li class="text-center text-gray-400 text-sm py-4">첫 번째 댓글을 남겨보세요!</li>
            @endif
        </ul>

        @if(($_SESSION['level'] ?? 0) >= $board->comment_level)
        <form action="{{ $currentUrl }}/comment" method="POST" class="flex items-start space-x-2">
            <textarea name="content" class="w-full border rounded p-2 text-sm focus:ring-2 focus:ring-blue-200 resize-none h-20" placeholder="댓글을 입력하세요..." required></textarea>
            <button class="bg-blue-600 text-white px-4 py-2 rounded h-20 font-bold hover:bg-blue-700 text-sm">등록</button>
        </form>
        @else
            <div class="text-center text-gray-400 text-sm py-2 border rounded bg-white">
                댓글 쓰기 권한이 없습니다.
            </div>
        @endif
    </div>

</div>
@endsection