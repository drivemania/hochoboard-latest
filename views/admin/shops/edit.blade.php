@extends('layouts.admin')
@section('title', '상점 관리')
@section('header', '상점 관리')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    
    <div class="bg-white rounded-lg shadow p-6" x-data="{ preview: '{{ $shop->npc_image_path ? $base_path . $shop->npc_image_path : '' }}' }">
        <h3 class="text-xl font-bold mb-4 border-b pb-2">상점 정보 수정</h3>
        
        <form action="{{ $base_path }}/admin/shops/update" method="POST" enctype="multipart/form-data" class="flex gap-6">
            <input type="hidden" name="id" value="{{ $shop->id }}">
            
            <div class="w-40 shrink-0 text-center">
                <div class="w-full h-40 bg-neutral-100 border-2 border-dashed border-neutral-300 rounded-lg flex items-center justify-center overflow-hidden mb-2 relative group">
                    <template x-if="preview">
                        <img :src="preview" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!preview">
                        <span class="text-neutral-400 text-xs">NPC 이미지</span>
                    </template>
                    
                    <label class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer transition">
                        <span class="text-white text-xs font-bold">이미지 변경</span>
                        <input type="file" name="npc_image" class="hidden" accept="image/*" @change="preview = URL.createObjectURL($event.target.files[0])">
                    </label>
                </div>
                <div>
                    <input type="text" name="npc_name" value="{{ $shop->npc_name }}" class="w-full border rounded p-2 text-sm" placeholder="NPC이름">
                </div>
            </div>

            <div class="flex-1 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">상점 이름</label>
                        <input type="text" name="name" value="{{ $shop->name }}" class="w-full border rounded p-2 text-sm" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-neutral-700 mb-1">상태</label>
                        <select name="is_open" class="w-full border rounded p-2 text-sm">
                            <option value="1" {{ $shop->is_open ? 'selected' : '' }}>🟢 운영중</option>
                            <option value="0" {{ !$shop->is_open ? 'selected' : '' }}>🔴 비공개</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">상점 대사/설명</label>
                    <textarea name="description" rows="2" class="w-full border rounded p-2 text-sm">{{ $shop->description }}</textarea>
                </div>
                <div class="text-right">
                    <button type="submit" class="bg-amber-500 text-white px-4 py-2 rounded text-sm font-bold hover:bg-amber-700">저장</button>
                </div>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-xl font-bold mb-4 border-b pb-2 flex justify-between items-center">
            <span>진열된 물품</span>
            <button @click="$dispatch('open-add-modal')" class="bg-green-600 text-white px-3 py-1.5 rounded text-xs font-bold hover:bg-green-700">
                + 물품 추가하기
            </button>
        </h3>

        @if($shopItems->isEmpty())
            <div class="text-center py-8 text-neutral-400 text-sm border border-dashed rounded">
                진열된 아이템이 없습니다.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-neutral-50 text-neutral-700 font-bold border-b">
                        <tr>
                            <th class="p-3">아이템</th>
                            <th class="p-3 text-right">가격</th>
                            <th class="p-3 text-center">구매 제한</th>
                            <th class="p-3 text-center">관리</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($shopItems as $sItem)
                        <tr class="hover:bg-neutral-50">
                            <td class="p-3 flex items-center gap-3">
                                <div class="w-10 h-10 border rounded bg-neutral-50 flex items-center justify-center shrink-0">
                                    @if($sItem->icon_path)
                                        <img src="{{ $base_path . $sItem->icon_path }}" class="w-full h-full object-contain">
                                    @else
                                        <span>📦</span>
                                    @endif
                                </div>
                                <div>
                                    <div class="font-bold">{{ $sItem->name }}</div>
                                    @if($sItem->is_binding) <span class="text-[10px] text-red-500 bg-red-50 px-1 rounded">귀속</span> @endif
                                </div>
                            </td>
                            <td class="p-3 text-right font-bold text-amber-700">
                                {{ number_format($sItem->price) }} {{ $group->point_name }}
                            </td>
                            <td class="p-3 text-center text-neutral-600">
                                @if($sItem->purchase_limit == 0)
                                    <span class="text-green-600 text-xs">무제한</span>
                                @else
                                    <span class="font-bold">{{ $sItem->purchase_limit }}</span>회
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <form action="{{ $base_path }}/admin/shops/items/delete" method="POST" onsubmit="return confirm('진열을 해제하시겠습니까?');">
                                    <input type="hidden" name="shop_item_id" value="{{ $sItem->id }}">
                                    <button class="text-red-500 hover:underline text-xs">삭제</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div x-data="{ open: false }" 
     @open-add-modal.window="open = true" 
     x-show="open" 
     class="fixed inset-0 z-50 flex items-center justify-center px-4" 
     x-cloak>
    
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="open = false"></div>
    
    <div class="bg-white w-full max-w-md rounded-lg shadow-xl relative z-10 p-6">
        <h3 class="text-lg font-bold mb-4">새 물품 진열</h3>
        
        <form action="{{ $base_path }}/admin/shops/items/add" method="POST">
            <input type="hidden" name="shop_id" value="{{ $shop->id }}">
            
            <div class="mb-4">
                <label class="block text-xs font-bold text-neutral-700 mb-1">아이템 선택</label>
                <select id="item-search" name="item_id" placeholder="아이템 검색..." autocomplete="off" required>
                    <option value="">선택하세요</option>
                    @foreach($allItems as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">판매 가격 (포인트)</label>
                    <input type="number" name="price" value="0" min="0" class="w-full border rounded p-2 text-right" required>
                </div>
                <div>
                    <label class="block text-xs font-bold text-neutral-700 mb-1">구매 제한 (0:무제한)</label>
                    <input type="number" name="purchase_limit" value="0" min="0" class="w-full border rounded p-2 text-right" required>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" @click="open = false" class="px-4 py-2 bg-neutral-100 text-neutral-600 rounded text-sm font-bold">취소</button>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded text-sm font-bold hover:bg-green-700">진열하기</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    new TomSelect("#item-search", {
        create: false,
        sortField: { field: "text", direction: "asc" },
        maxItems: 1
    });
});
</script>
@endpush
@endsection