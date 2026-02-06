@extends('layouts.admin')
@section('title', '상점 관리')
@section('header', '상점 관리')

@section('content')
<div x-data="{ createModalOpen: false }">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-2xl font-bold text-neutral-800">상점 관리</h2>
            <p class="text-sm text-neutral-500">생성된 상점을 관리하고 물품을 진열합니다.</p>
        </div>
        <button @click="createModalOpen = true" class="bg-amber-500 hover:bg-amber-700 text-white px-4 py-2 rounded-lg font-bold shadow transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            새 상점 생성
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        @if($shops->isEmpty())
            <div class="p-10 text-center text-neutral-400">
                <div class="text-4xl mb-2">🏪</div>
                <p>생성된 상점이 없습니다.</p>
                <button @click="createModalOpen = true" class="text-amber-500 hover:underline text-sm font-bold mt-2">첫 상점을 만들어보세요!</button>
            </div>
        @else
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-neutral-50 text-neutral-600 text-xs uppercase border-b">
                        <th class="p-4 font-bold w-16 text-center">ID</th>
                        <th class="p-4 font-bold">상점 정보</th>
                        <th class="p-4 font-bold">소속 그룹</th>
                        <th class="p-4 font-bold text-center">상태</th>
                        <th class="p-4 font-bold text-center">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    @foreach($shops as $shop)
                    <tr class="hover:bg-neutral-50 transition">
                        <td class="p-4 text-center text-neutral-400 font-mono text-xs">
                            {{ $shop->id }}
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-neutral-100 border border-neutral-200 overflow-hidden shrink-0 flex items-center justify-center">
                                    @if($shop->npc_image_path)
                                        <img src="{{ $base_path . $shop->npc_image_path }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xl">👤</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-bold text-neutral-800"><a href="{{ $base_path }}/au/{{ $shop->group_slug }}/shop/{{ $shop->id }}" target="_blank">{{ $shop->name }}</a></div>
                                    <div class="text-xs text-neutral-500 truncate max-w-xs">{{ $shop->description ?: '설명 없음' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <span class="inline-block bg-neutral-100 text-neutral-600 text-xs px-2 py-1 rounded font-bold border border-neutral-200">
                                {{ $shop->group_name }}
                            </span>
                        </td>
                        <td class="p-4 text-center">
                            @if($shop->is_open)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    🟢 운영중
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-800">
                                    🔴 비공개
                                </span>
                            @endif
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ $base_path }}/admin/shops/{{ $shop->id }}" 
                                   class="text-sm font-bold text-white bg-amber-400 hover:bg-amber-500 px-3 py-1.5 rounded transition">
                                    관리
                                </a>

                                <form action="{{ $base_path }}/admin/shops/delete" method="POST" onsubmit="return confirm('상점을 삭제하면 진열된 물품 정보도 모두 사라집니다.\n정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="id" value="{{ $shop->id }}">
                                    <button type="submit" class="p-1.5 text-sm text-red-400 hover:text-red-500 rounded transition">
                                        삭제
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div x-show="createModalOpen" class="fixed inset-0 z-50 flex items-center justify-center px-4" x-cloak style="display: none;">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="createModalOpen = false"></div>

        <div class="bg-white w-full max-w-lg rounded-xl shadow-2xl relative z-10 overflow-hidden transform transition-all">
            <div class="bg-neutral-50 px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-bold text-neutral-800">새 상점 만들기</h3>
                <button @click="createModalOpen = false" class="text-neutral-400 hover:text-neutral-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ $base_path }}/admin/shops" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                
                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">소속 그룹 <span class="text-red-500">*</span></label>
                    <select name="group_id" class="w-full border-neutral-300 rounded-lg text-sm focus:ring-amber-400" required>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">상점 이름 <span class="text-red-500">*</span></label>
                    <input type="text" name="name" class="w-full border-neutral-300 rounded-lg text-sm focus:ring-amber-400" placeholder="예: 신년 이벤트 상점" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">설명 (NPC 대사)</label>
                    <textarea name="description" rows="2" class="w-full border-neutral-300 rounded-lg text-sm focus:ring-amber-400" placeholder="상점에 입장했을 때 표시될 문구입니다."></textarea>
                </div>

                <div x-data="{ preview: null }">
                    <label class="block text-xs font-bold text-neutral-700 mb-1">NPC 이미지</label>
                    <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-neutral-100 rounded-lg border border-dashed border-neutral-300 flex items-center justify-center overflow-hidden shrink-0">
                            <template x-if="preview">
                                <img :src="preview" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!preview">
                                <span class="text-neutral-400 text-xs">No Image</span>
                            </template>
                        </div>
                        <input type="file" name="npc_image" class="block w-full text-xs text-neutral-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100"
                               accept="image/*"
                               @change="preview = URL.createObjectURL($event.target.files[0])">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">운영 상태</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="is_open" value="1" class="text-amber-500 focus:ring-amber-400" checked>
                            <span class="ml-2 text-sm text-neutral-700">운영중</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="is_open" value="0" class="text-neutral-400 focus:ring-neutral-400">
                            <span class="ml-2 text-sm text-neutral-500">비공개</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t flex justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-lg text-sm font-bold">취소</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 hover:bg-amber-700 text-white rounded-lg text-sm font-bold">상점 생성</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection