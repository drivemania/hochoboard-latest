@extends('layouts.admin')
@section('title', '아이템 관리')
@section('header', '아이템 관리')

@section('content')
<div x-data="itemManager('{{ $base_path }}')" @add-item.window="addRandomBoxItem($event.detail.id, $event.detail.name)">
    
    <div class="flex justify-end items-center mb-6">
        <button @click="openModal()" class="bg-amber-500 text-white px-4 py-2 rounded font-bold hover:bg-amber-700">
            + 아이템 등록
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-sm text-left">
            <thead class="bg-neutral-50 text-neutral-700 font-bold border-b">
                <tr>
                    <th class="p-3 w-16 text-center">ID</th>
                    <th class="p-3 w-16">아이콘</th>
                    <th class="p-3">이름/설명</th>
                    <th class="p-3 w-32">효과 타입</th>
                    <th class="p-3 w-24">판매가</th>
                    <th class="p-3 w-24 text-center">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($items as $item)
                <tr class="hover:bg-neutral-50">
                    <td class="p-3 text-center text-neutral-500">{{ $item->id }}</td>
                    <td class="p-3">
                        @if($item->icon_path)
                            <img src="{{ $base_path . $item->icon_path }}" class="w-10 h-10 rounded border bg-neutral-100">
                        @else
                            <div class="w-10 h-10 rounded border bg-neutral-100 flex items-center justify-center text-xs">No Img</div>
                        @endif
                    </td>
                    <td class="p-3">
                        <div class="font-bold text-neutral-800">{{ $item->name }}</div>
                        <div class="text-xs text-neutral-500 truncate max-w-xs">{{ $item->description }}</div>
                    </td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded text-xs font-bold 
                            {{ $item->effect_type == 'none' ? 'bg-neutral-100 text-neutral-600' : 
                              ($item->effect_type == 'lottery' ? 'bg-yellow-100 text-yellow-700' : 'bg-purple-100 text-purple-700') }}">
                            {{ $item->effect_type }}
                        </span>
                    </td>
                    <td class="p-3 text-neutral-600">
                        @if($item->is_sellable)
                            {{ number_format($item->sell_price) }} P
                        @else
                            <span class="text-red-400 text-xs">판매불가</span>
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        <button @click='editItem(@json($item))' class="text-amber-500 hover:underline mr-2">수정</button>
                        <form action="{{ $base_path }}/admin/items/delete" method="POST" class="inline-block" onsubmit="return confirm('삭제하시겠습니까?')">
                            <input type="hidden" name="id" value="{{ $item->id }}">
                            <button class="text-red-500 hover:underline">삭제</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div x-show="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <form action="{{ $base_path }}/admin/items/store" method="POST" enctype="multipart/form-data" class="p-6">
                
                <h3 class="text-xl font-bold mb-4" x-text="form.id ? '아이템 수정' : '새 아이템 등록'"></h3>
                <input type="hidden" name="id" x-model="form.id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1">아이템명</label>
                        <input type="text" name="name" x-model="form.name" class="w-full border rounded p-2" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-bold text-neutral-700 mb-1">설명</label>
                        <textarea name="description" x-model="form.description" class="w-full border rounded p-2" rows="2"></textarea>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-1">
                            <label class="block text-sm font-bold text-neutral-700 mb-1">아이콘 이미지</label>
                            <input type="file" name="icon" class="w-full text-sm border rounded p-1">
                            <input type="hidden" name="existing_icon_path" x-model="form.icon_path">
                        </div>
                        <template x-if="form.icon_path">
                            <div class="w-16 h-16 shrink-0 border rounded bg-neutral-50 flex items-center justify-center">
                                <img :src="basePath + form.icon_path" class="max-w-full max-h-full">
                            </div>
                        </template>
                    </div>
                </div>

                <hr class="my-6 border-neutral-200">

                <div class="mb-4">
                    <label class="block text-sm font-bold text-amber-700 mb-1">✨ 아이템 효과</label>
                    <select name="effect_type" x-model="form.effect_type" class="w-full border border-amber-200 bg-amber-50 rounded p-2 font-bold">
                        <option value="none">효과 없음</option>
                        <option value="lottery">복권 (재화 획득)</option>
                        <option value="create_item">아이템 생성권</option>
                        <option value="random_box">랜덤 박스</option>
                    </select>
                </div>

                <div x-show="form.effect_type === 'lottery'" class="bg-yellow-50 p-3 rounded border border-yellow-200 mb-4">
                    <p class="text-xs text-yellow-800 mb-2 font-bold">💰 획득 재화 범위 설정</p>
                    <div class="flex gap-2">
                        <input type="number" name="lottery_min" x-model="form.lottery_min" placeholder="최소" class="w-1/2 border rounded p-2">
                        <span class="py-2">~</span>
                        <input type="number" name="lottery_max" x-model="form.lottery_max" placeholder="최대" class="w-1/2 border rounded p-2">
                    </div>
                </div>

                <div x-show="form.effect_type === 'random_box'" class="bg-purple-50 p-3 rounded border border-purple-200 mb-4">
                    <p class="text-xs text-purple-800 mb-2 font-bold">🎁 구성품 및 확률 설정</p>
                    
                    <div class="mb-3">
                        <select id="random-box-search" placeholder="구성품을 검색해서 추가하세요..." autocomplete="off">
                            <option value="">아이템 검색...</option>
                            @foreach($items as $optItem)
                                <option value="{{ $optItem->id }}" data-name="{{ $optItem->name }}">{{ $optItem->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2 max-h-60 overflow-y-auto custom-scrollbar pr-1">
                        <template x-for="(item, index) in randomBoxItems" :key="item.item_id">
                            <div class="flex items-center justify-between bg-white p-2 rounded border border-purple-100 shadow-sm">
                                
                                <div class="flex items-center flex-1 min-w-0 mr-2">
                                    <span class="text-xs font-bold text-neutral-700 truncate" x-text="item.name"></span>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <div class="flex items-center border rounded bg-neutral-50">
                                        <span class="text-[10px] text-neutral-500 px-1.5">가중치</span>
                                        <input type="number" x-model.number="item.weight" @input="updateJson()" min="1" class="w-14 p-1 text-right text-xs bg-transparent outline-none font-mono" placeholder="1">
                                    </div>
                                    
                                    <button type="button" @click="removeItem(item.item_id)" class="text-neutral-400 hover:text-red-500 p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </template>
                        
                        <div x-show="randomBoxItems.length === 0" class="text-xs text-neutral-400 text-center py-4 border border-dashed border-purple-200 rounded">
                            구성품이 없습니다. 아이템을 검색해서 추가해주세요.
                        </div>
                    </div>

                    <input type="hidden" name="random_box_json" x-model="form.random_box_json">
                    
                    <div class="mt-3 text-[10px] text-neutral-500 bg-white p-2 rounded border">
                        <p>※ <b>가중치란?</b> 해당 아이템이 나올 상대적 확률입니다.</p>
                        <p>예: A아이템(1), B아이템(9) 설정 시, A는 10%, B는 90% 확률로 획득됩니다.</p>
                    </div>
                </div>

                <hr class="my-6 border-neutral-200">

                <div class="flex gap-6 bg-neutral-50 p-3 rounded mb-4 border border-neutral-200">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_binding" x-model="form.is_binding" class="w-4 h-4 text-red-600 rounded focus:ring-red-500">
                        <span class="ml-2 text-sm font-bold text-neutral-700">귀속 아이템</span>
                    </label>

                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_permanent" x-model="form.is_permanent" class="w-4 h-4 text-green-600 rounded focus:ring-green-500">
                        <span class="ml-2 text-sm font-bold text-neutral-700">영구 아이템</span>
                    </label>
                </div>

                <div class="flex items-center justify-between bg-neutral-50 p-3 rounded border border-neutral-200">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="is_sellable" x-model="form.is_sellable" class="w-4 h-4 text-amber-500">
                        <span class="ml-2 text-sm font-bold">판매 가능</span>
                    </label>
                    <div x-show="form.is_sellable" class="flex items-center gap-2">
                        <span class="text-sm">판매가:</span>
                        <input type="number" name="sell_price" x-model="form.sell_price" class="w-24 border rounded p-1 text-right">
                        <span class="text-sm">P</span>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" @click="isModalOpen = false" class="px-4 py-2 bg-neutral-200 rounded hover:bg-neutral-300 font-bold text-neutral-700">취소</button>
                    <button type="submit" class="px-4 py-2 bg-amber-500 rounded hover:bg-amber-700 font-bold text-white">저장</button>
                </div>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
function itemManager(basePath = '') {
    return {
        basePath: basePath,
        isModalOpen: false,
        
        randomBoxItems: [], 

        form: {
            id: null,
            name: '',
            description: '',
            icon_path: '',
            effect_type: 'none',
            is_sellable: false,
            is_binding: false,
            is_permanent: false,
            sell_price: 0,
            lottery_min: 0,
            lottery_max: 0,
            random_box_json: ''
        },
        
        addRandomBoxItem(id, name) {
            if (this.randomBoxItems.find(i => i.item_id == id)) return;

            this.randomBoxItems.push({
                item_id: parseInt(id),
                name: name,
                weight: 1
            });
            this.updateJson();
        },

        removeItem(id) {
            this.randomBoxItems = this.randomBoxItems.filter(i => i.item_id !== id);
            this.updateJson();
        },

        updateJson() {
            if (this.randomBoxItems.length === 0) {
                this.form.random_box_json = '';
                return;
            }
            const data = { pool: this.randomBoxItems };
            this.form.random_box_json = JSON.stringify(data);
        },

        openModal() {
            this.resetForm();
            this.isModalOpen = true;
        },

        editItem(item) {
            this.resetForm();

            this.form.id = item.id;
            this.form.name = item.name;
            this.form.description = item.description;
            this.form.icon_path = item.icon_path;
            this.form.effect_type = item.effect_type;
            this.form.is_sellable = item.is_sellable == 1;
            this.form.is_binding = item.is_binding == 1;
            this.form.is_permanent = item.is_permanent == 1;
            this.form.sell_price = item.sell_price;

            if (item.effect_data) {
                try {
                    const data = typeof item.effect_data === 'string' ? JSON.parse(item.effect_data) : item.effect_data;

                    if (item.effect_type === 'lottery') {
                        this.form.lottery_min = data.min_point || 0;
                        this.form.lottery_max = data.max_point || 0;
                    } 
                    else if (item.effect_type === 'random_box') {
                        if (data.pool && Array.isArray(data.pool)) {
                            this.randomBoxItems = data.pool.map(p => ({
                                item_id: p.item_id,
                                weight: p.weight,
                                name: this.findItemName(p.item_id) 
                            }));
                            this.form.random_box_json = JSON.stringify(data);
                        }
                    }
                } catch (e) {
                    console.error('JSON parsing error', e);
                }
            }

            this.isModalOpen = true;
        },

        resetForm() {
            this.randomBoxItems = [];
            this.form = {
                id: null,
                name: '',
                description: '',
                icon_path: '',
                effect_type: 'none',
                is_sellable: false,
                is_binding: false,
                is_permanent: false,
                sell_price: 0,
                lottery_min: 0,
                lottery_max: 0,
                random_box_json: ''
            };
        },

        findItemName(id) {
            const option = document.querySelector(`#random-box-search option[value="${id}"]`);
            return option ? (option.dataset.name || option.innerText) : '삭제된 아이템';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    new TomSelect("#random-box-search", {
        maxItems: 1,
        valueField: 'value',
        labelField: 'text',
        searchField: 'text',
        create: false,
        onItemAdd: function(value, item) {
            const name = item.dataset.name || item.innerText;
            
            window.dispatchEvent(new CustomEvent('add-item', {
                detail: { id: value, name: name }
            }));
            
            this.clear();
        }
    });
});
</script>
@endpush
@endsection