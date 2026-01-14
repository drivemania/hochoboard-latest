@extends($themeLayout)

@section('content')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@php
$searchTarget = $_GET['search_target'] ?? "";
$keyword = $_GET['keyword'] ?? "";
@endphp

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

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <div class="flex flex-col md:flex-row justify-between items-end mb-4 border-b pb-2 gap-4 md:gap-0">
            <div class="w-full md:w-auto">
                <h2 class="text-2xl font-bold text-gray-800">{{ $board->title }}</h2>
            </div>
            <form class="flex w-full md:w-auto bg-white border border-gray-200 rounded-full px-4 py-2 shadow-sm focus-within:ring-2 focus-within:ring-indigo-100 focus-within:border-indigo-300 transition-all">
                <select name="search_target" class="text-sm text-gray-500 bg-transparent border-none outline-none mr-2">
                    <option value="title" {{ $searchTarget == "title" ? "selected" : "" }}>제목</option>
                    <option value="content" {{ $searchTarget == "content" ? "selected" : "" }}>내용</option>
                    <option value="member" {{ $searchTarget == "member" ? "selected" : "" }}>멤버</option>
                </select>
                <input type="text" name="keyword" placeholder="검색..." class="flex-1 text-m outline-none text-gray-700 placeholder-gray-400 bg-transparent min-w-0" value="{{ $keyword }}">
                <button type="submit" class="text-gray-400 hover:text-indigo-600 transition-colors shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-gray-500 border-b text-sm">
                        <th class="hidden md:table-cell py-3 px-2 w-16 text-center whitespace-nowrap">번호</th>
                        
                        <th class="py-3 px-2">제목</th>
                        
                        <th class="hidden md:table-cell py-3 px-2 w-24 text-center whitespace-nowrap">작성자</th>
                        
                        <th class="py-3 px-2 w-20 md:w-24 text-center whitespace-nowrap">날짜</th>
                        
                        <th class="hidden md:table-cell py-3 px-2 w-16 text-center whitespace-nowrap">조회</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-700">
                    @forelse($documents as $doc)
                    <tr class="hover:bg-gray-50 border-b last:border-0 transition">
                        
                        <td class="hidden md:table-cell py-3 px-2 text-center">
                            @if($doc->is_notice)
                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 inset-ring inset-ring-indigo-700/10">공지</span>
                            @else
                            {{ $doc->doc_num }}
                            @endif
                        </td>
                        
                        <td class="py-3 px-2">
                            <a href="{{ $currentUrl }}/{{ $doc->doc_num }}" class="hover:underline hover:text-blue-600 block break-all">
                                @if($doc->is_notice)
                                    <span class="md:hidden inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-700 mr-1 align-middle">공지</span>
                                @endif

                                {{ $doc->title }}
                                
                                @if($doc->comment_count > 0)
                                    <span class="text-red-500 text-xs font-bold">[{{ $doc->comment_count }}]</span>
                                @endif
                                @if($doc->is_secret)
                                    🔒
                                @endif

                                <div class="md:hidden text-xs text-gray-400 mt-1">
                                    by {{ $doc->nickname }}
                                </div>
                            </a>
                        </td>
                        
                        <td class="hidden md:table-cell py-3 px-2 text-center truncate max-w-[100px]">{{ $doc->nickname }}</td>
                        
                        <td class="py-3 px-2 text-center text-gray-500 text-xs whitespace-nowrap">
                            {{ date('m-d', strtotime($doc->created_at)) }}
                        </td>
                        
                        <td class="hidden md:table-cell py-3 px-2 text-center text-gray-400 text-xs">{{ $doc->hit }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-gray-500">
                            게시글이 없습니다. 첫 번째 글을 작성해보세요!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
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
        @if( ($board->write_level == 1) || (isset($_SESSION['user_id']) && $_SESSION['level'] >= $board->write_level) )
        <div class="mt-6 flex justify-self-end space-x-1">
            <a href="{{ $currentUrl }}/write" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-bold transition">
                ✏️ 글쓰기
            </a>
        </div>
        @endif
    </div>
</div>
@endsection