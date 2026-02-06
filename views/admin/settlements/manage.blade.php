@extends('layouts.admin')
@section('title', '정산 관리 > ' . $group->name)
@section('header', '정산 관리 > ' . $group->name)

@section('content')
<div x-data="settlementsManager()" @item-selected.window="addItem($event.detail.id, $event.detail.name)">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">정산/지급 관리 - {{ $group->name }}</h2>
        <a href="{{ $base_path }}/admin/settlements" class="text-neutral-500 hover:text-neutral-700">그룹 다시 선택</a>
    </div>

    <form action="{{ $base_path }}/admin/settlements/distribute" method="POST" onsubmit="return confirm('정말로 지급하시겠습니까?');">
        <input type="hidden" name="group_id" value="{{ $group->id }}">
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-lg mb-4 border-b pb-2">대상 선택</h3>
                    
                    <div class="flex space-x-2 mb-4">
                        <button type="button" @click="targetType = 'selection'" 
                            :class="targetType === 'selection' ? 'bg-amber-500 text-white' : 'bg-neutral-100 text-neutral-600'"
                            class="px-4 py-2 rounded text-sm font-bold flex-1">
                            개별 선택
                        </button>
                        @if(!empty($customFields))
                        <button type="button" @click="targetType = 'filter'" 
                            :class="targetType === 'filter' ? 'bg-amber-500 text-white' : 'bg-neutral-100 text-neutral-600'"
                            class="px-4 py-2 rounded text-sm font-bold flex-1">
                            조건 검색 (필터)
                        </button>
                        @endif
                        <button type="button" @click="targetType = 'all'" 
                            :class="targetType === 'all' ? 'bg-amber-500 text-white' : 'bg-neutral-100 text-neutral-600'"
                            class="px-4 py-2 rounded text-sm font-bold flex-1">
                            그룹 전체
                        </button>
                    </div>
                    <input type="hidden" name="target_type" x-model="targetType">

                    <div x-show="targetType === 'selection'">
                        
                        <div class="mb-2">
                            <input type="text" x-model="searchKeyword" placeholder="캐릭터명 또는 오너명 검색..." class="w-full border p-2 rounded text-sm focus:ring-2 focus:ring-amber-400 outline-none">
                        </div>

                        <div x-show="selectedIds.length > 0" class="mb-2 flex flex-wrap gap-1 p-2 bg-amber-50 rounded border border-amber-100 max-h-24 overflow-y-auto custom-scrollbar">
                            <template x-for="id in selectedIds" :key="'tag-' + id">
                                <div class="inline-flex items-center bg-white border border-amber-200 text-amber-700 text-xs font-bold px-2 py-1 rounded-full shadow-sm">
                                    <span x-text="getCharName(id)"></span>
                                    <button type="button" @click="toggleSelection(id)" class="ml-1 text-amber-400 hover:text-amber-500 focus:outline-none">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>
                            </template>
                            <div class="w-full text-[10px] text-right text-amber-400 mt-1">
                                총 <span x-text="selectedIds.length"></span>명 선택됨
                            </div>
                        </div>

                        <div class="h-64 overflow-y-auto border rounded p-2 bg-neutral-50 custom-scrollbar">
                            <template x-for="char in filteredCharacters" :key="char.id">
                                <label class="flex items-center p-2 hover:bg-white rounded cursor-pointer transition-colors" 
                                       :class="selectedIds.includes(char.id) ? 'bg-amber-50 border-amber-100' : ''">
                                    
                                    <input type="checkbox" :value="char.id" x-model="selectedIds" class="w-4 h-4 text-amber-500 rounded mr-2 border-neutral-300 focus:ring-amber-400">
                                    
                                    <div class="flex-1">
                                        <div class="font-bold text-sm text-neutral-800" x-text="char.name"></div>
                                        <div class="text-xs text-neutral-500" x-text="'오너: ' + char.owner_name"></div>
                                    </div>
                                    
                                    <div x-show="selectedIds.includes(char.id)" class="text-amber-500">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                </label>
                            </template>
                            
                            <div x-show="filteredCharacters.length === 0" class="text-center text-neutral-400 py-8 text-sm">
                                <p class="mb-1">검색 결과가 없습니다.</p>
                            </div>
                        </div>

                        <template x-for="id in selectedIds" :key="'hidden-' + id">
                            <input type="hidden" name="selected_chars[]" :value="id">
                        </template>

                    </div>

                    @if(!empty($customFields))
                    <div x-show="targetType === 'filter'" class="space-y-3 bg-neutral-50 p-4 rounded border">
                        <p class="text-xs text-neutral-500 mb-2">※ 선택한 조건에 해당하는 모든 캐릭터에게 지급됩니다.</p>
                        @foreach($customFields as $field)
                            @if(in_array($field['type'], ['select', 'radio']))
                            <div>
                                <label class="block text-xs font-bold text-neutral-700 mb-1">{{ $field['name'] }}</label>
                                <select name="filters[{{ $field['name'] }}]" class="w-full border text-sm rounded p-2">
                                    <option value="">전체</option>
                                    @foreach(explode(',', $field['options']) as $opt)
                                        <option value="{{ trim($opt) }}">{{ trim($opt) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        @endforeach
                    </div>
                    @endif

                    <div x-show="targetType === 'all'" class="p-4 bg-yellow-50 text-yellow-800 text-sm rounded border border-yellow-200">
                        ⚠️ <b>주의:</b> 이 그룹에 속한 <b>모든 캐릭터</b>에게 지급됩니다.<br>
                        포인트는 회원당 1회만 지급되지만, 아이템은 모든 캐릭터에게 각각 지급됩니다.
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                    <h3 class="font-bold text-lg mb-4 border-b pb-2">지급 내용</h3>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-neutral-700 mb-1">포인트 지급 ({{ $group->point_name }})</label>
                        <input type="number" name="point_amount" value="0" class="w-full border border-neutral-300 rounded p-2 text-right font-mono">
                        <p class="text-xs text-neutral-400 mt-1">* 차감하려면 음수(-) 입력</p>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-neutral-700 mb-1">아이템 지급</label>
                        
                        <select id="item-search" placeholder="아이템을 검색해서 추가하세요..." autocomplete="off">
                            <option value="">아이템 검색...</option>
                            @foreach($items as $item)
                                <option value="{{ $item->id }}" data-name="{{ $item->name }}">{{ $item->name }}</option>
                            @endforeach
                        </select>

                        <div class="mt-3 space-y-2">
                            <template x-for="(item, index) in selectedItems" :key="item.id">
                                <div class="flex items-center justify-between bg-neutral-50 p-2 rounded border border-neutral-200">
                                    
                                    <div class="flex items-center">
                                        <span class="text-sm font-bold text-neutral-700" x-text="item.name"></span>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <div class="flex items-center bg-white border rounded">
                                            <span class="text-xs text-neutral-400 px-2">개수</span>
                                            <input type="number" x-model="item.quantity" min="1" class="w-16 p-1 text-right text-sm border-l outline-none" required>
                                        </div>
                                        
                                        <button type="button" @click="removeItem(item.id)" class="text-neutral-400 hover:text-red-500 p-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <input type="hidden" :name="`items[${item.id}]`" :value="item.quantity">
                                </div>
                            </template>

                            <div x-show="selectedItems.length === 0" class="text-xs text-neutral-400 text-center py-2">
                                선택된 아이템이 없습니다.
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-bold text-neutral-700 mb-1">지급 사유 <span class="text-red-500">*</span></label>
                        <textarea name="reason" rows="3" required class="w-full border border-neutral-300 rounded p-2 text-sm" placeholder="예: 이벤트 참여 보상"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-amber-500 hover:bg-amber-700 text-white font-bold py-3 rounded shadow-lg transition transform active:scale-95">
                        지급 실행
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
function settlementsManager() {
    return {
        targetType: 'selection',
        searchKeyword: '',
        characters: @json($characters),
        
        selectedIds: [],
        selectedItems: [],

        get filteredCharacters() {
            if (this.searchKeyword === '') return this.characters;
            const lowerKeyword = this.searchKeyword.toLowerCase();
            return this.characters.filter(char => {
                return char.name.toLowerCase().includes(lowerKeyword) || 
                       char.owner_name.toLowerCase().includes(lowerKeyword);
            });
        },

        addItem(id, name) {
            const existing = this.selectedItems.find(i => i.id == id);
            if (existing) {
                existing.quantity++;
                return;
            }
            this.selectedItems.push({
                id: id,
                name: name,
                quantity: 1
            });
        },

        removeItem(id) {
            this.selectedItems = this.selectedItems.filter(i => i.id !== id);
        },

        getCharName(id) {
            const char = this.characters.find(c => c.id == id);
            return char ? char.name : 'Unknown';
        },

        toggleSelection(id) {
            const index = this.selectedIds.indexOf(parseInt(id)); 
            if (index === -1) {
                const strIndex = this.selectedIds.indexOf(String(id));
                if (strIndex === -1) {
                    this.selectedIds.push(id);
                } else {
                    this.selectedIds.splice(strIndex, 1);
                }
            } else {
                this.selectedIds.splice(index, 1);
            }
        }
    }
}


document.addEventListener('DOMContentLoaded', function() {
    var itemSelect =new TomSelect("#item-search", {
        maxItems: 1,
        valueField: 'value',
        labelField: 'text',
        searchField: 'text',
        create: false,
        onItemAdd: function(value, item) {
            const name = item.dataset.name || item.innerText;
            window.dispatchEvent(new CustomEvent('item-selected', {
                detail: { id: value, name: name }
            }));
            
            this.clear();
        }
    });
});
</script>
@endpush
@endsection