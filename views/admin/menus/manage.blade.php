@extends('layouts.admin')
@section('title', '메뉴 관리 > ' . $group->name)
@section('header', '메뉴 관리 > ' . $group->name)

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <div class="bg-white p-6 rounded-lg shadow-sm">
        <h3 class="font-bold text-lg mb-4 border-b pb-2 flex justify-between items-center">
            <span>📋 현재 메뉴 목록</span>
            <span class="text-xs text-gray-400 font-normal">드래그하여 순서 변경 가능</span>
        </h3>
        
        <ul class="space-y-3" id="menu-list">
            @foreach($menus as $menu)
            <li data-id="{{ $menu->id }}" class="flex justify-between items-center bg-gray-50 p-3 rounded border hover:shadow-sm transition cursor-move group">
                <div class="flex items-center">
                    <div class="mr-3 text-gray-400 group-hover:text-blue-500 cursor-grab">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </div>

                    <div>
                        <span class="font-bold text-gray-800">{{ $menu->title }}</span>
                        <a class="text-xs text-gray-500 block mt-1" href="{{ $base_path.'/au/'.$group->slug.'/'.$menu->slug }}" target="_blank">
                            URL: <span class="text-blue-600 font-mono">{{ $menu->slug }}</span>
                        </a>
                        
                        <div class="mt-1">
                            @if($menu->type === 'board')
                                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded">
                                    📄 게시판: {{ $menu->board_title }} (ID:{{ $menu->target_id }})
                                </span>
                            @elseif($menu->type === 'load')
                                <span class="text-xs bg-yellow-100 text-green-700 px-2 py-0.5 rounded">
                                    🎨 로드비 게시판: {{ $menu->board_title }} (ID:{{ $menu->target_id }})
                                </span>
                            @elseif($menu->type === 'character')
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded">
                                    🧙‍♂️ 캐릭터 게시판: {{ $menu->board_title }} (ID:{{ $menu->target_id }})
                                </span>
                            @elseif($menu->type === 'page')
                                <span class="text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded">
                                    📑 페이지: {{ $menu->board_title }} (ID:{{ $menu->target_id }})
                                </span>
                            @else
                                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded">
                                    🔗 기타
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <form action="{{ $base_path }}/admin/menus/delete" method="POST" onsubmit="return confirm('이 메뉴를 삭제하시겠습니까?');" class="ml-2">
                    <input type="hidden" name="menu_id" value="{{ $menu->id }}">
                    <input type="hidden" name="group_id" value="{{ $group->id }}">
                    <button class="text-red-500 hover:text-red-700 text-sm font-bold p-2 hover:bg-red-50 rounded">삭제</button>
                </form>
            </li>
            @endforeach
            
            @if($menus->isEmpty())
                <li class="text-center py-10 text-gray-400 bg-gray-50 rounded border border-dashed">
                    등록된 메뉴가 없습니다.<br>오른쪽에서 메뉴를 추가해주세요.
                </li>
            @endif
        </ul>
        <div class="mt-6 text-center">
            <a href="{{ $base_path }}/admin/menus" class="text-gray-500 text-sm hover:underline">⬅ 그룹 선택으로 돌아가기</a>
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm h-fit sticky top-6">
        <h3 class="font-bold text-lg mb-4 border-b pb-2">➕ 메뉴 추가</h3>
        
        <form action="{{ $base_path }}/admin/menus" method="POST" x-data="{ menuType: 'board' }">
            <input type="hidden" name="group_id" value="{{ $group->id }}">
            
            <div class="mb-5">
                <label class="block text-sm font-bold mb-2 text-gray-700">메뉴 타입</label>
                <select name="type" x-model="menuType" class="w-full border border-gray-300 rounded px-3 py-2 bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="board">📄 일반 게시판 연결</option>
                    <option value="load">🎨 로드비 게시판 연결</option>
                    <option value="character">🧙‍♂️ 캐릭터 게시판 연결</option>
                    <option value="page">📑 페이지 연결</option>
                </select>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold mb-2 text-gray-700">연결할 게시판 원본</label>
                <select name="target_id" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="">게시판을 선택하세요</option>
                    <optgroup label="📄 일반 게시판" x-show="menuType === 'board'">
                        @foreach($allBoards as $board)
                            @if($board->type === 'document')
                                <option value="{{ $board->id }}">{{ $board->title }} (ID: {{ $board->id }})</option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="🎨 로드비 게시판" x-show="menuType === 'load'">
                        @foreach($allBoards as $board)
                            @if($board->type === 'load')
                                <option value="{{ $board->id }}">{{ $board->title }} (ID: {{ $board->id }})</option>
                            @endif
                        @endforeach
                    </optgroup>
            
                    <optgroup label="🧙‍♂️ 캐릭터 게시판" x-show="menuType === 'character'">
                        @foreach($allBoards as $board)
                            @if($board->type === 'character')
                                <option value="{{ $board->id }}">{{ $board->title }} (ID: {{ $board->id }})</option>
                            @endif
                        @endforeach
                    </optgroup>

                    <optgroup label="📑 페이지" x-show="menuType === 'page'">
                        @foreach($allBoards as $board)
                            @if($board->type === 'page')
                                <option value="{{ $board->id }}">{{ $board->title }} (ID: {{ $board->id }})</option>
                            @endif
                        @endforeach
                    </optgroup>
                </select>
                <p class="text-xs text-gray-500 mt-1">※ '게시판 관리'에서 생성한 게시판 목록입니다.</p>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-bold mb-2 text-gray-700">메뉴 이름</label>
                <input type="text" name="title" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="예: 자유게시판, 멤버소개" required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold mb-2 text-gray-700">접속 URL (Slug)</label>
                <div class="flex items-center">
                    <span class="bg-gray-100 border border-r-0 border-gray-300 rounded-l px-3 py-2 text-gray-500 text-sm">/{{ $group->slug }}/</span>
                    <input type="text" name="slug" class="w-full border border-gray-300 rounded-r px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="free, member" required>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-lg font-bold hover:bg-indigo-700 transition shadow-md">
                메뉴 추가하기
            </button>
        </form>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('menu-list');
    
    if(el) {
        var sortable = Sortable.create(el, {
            animation: 150,
            handle: '.cursor-grab',
            ghostClass: 'bg-indigo-50',
            
            onEnd: function (evt) {
                var order = [];
                el.querySelectorAll('li').forEach(function(item) {
                    order.push(item.getAttribute('data-id'));
                });

                fetch('{{ $base_path }}/admin/menus/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ order: order })
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                    } else {
                        alert('순서 저장 실패');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('통신 오류가 발생했습니다.');
                });
            }
        });
    }
});
</script>
@endpush
@endsection