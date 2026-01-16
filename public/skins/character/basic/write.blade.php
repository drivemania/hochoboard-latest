@extends($themeLayout)

@section('content')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@php
$cancelUrl = $_SERVER['HTTP_REFERER'];
if($character){
    $cancelUrl = "$currentUrl/$character->id";
}

@endphp

<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow border">
    <h2 class="text-xl font-bold mb-4 pb-2 border-b">
        {{ $mode === 'edit' ? '캐릭터 수정' : '새 캐릭터 생성' }}
    </h2>

    <form action="{{ $actionUrl }}" method="POST" enctype="multipart/form-data">
        
        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">캐릭터 이름</label>
            <input type="text" name="name" value="{{ $character->name ?? '' }}" class="w-full border rounded px-3 py-2" required placeholder="">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">한마디</label>
            <input type="text" name="description" value="{{ $character->description ?? '' }}" class="w-full border rounded px-3 py-2" placeholder="">
        </div>
        
        <div class="mb-4">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_main" value="1" class="w-4 h-4 text-blue-600 rounded" {{ ($character->is_main ?? false) ? 'checked' : '' }}>
                <span class="ml-2 text-sm text-gray-700 font-bold">대표 캐릭터로 설정</span>
            </label>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">두상 이미지</label>
            <input type="file" name="image_path" class="w-full border rounded px-3 py-2 bg-white">
            <div class="text-center text-xs text-gray-400 font-bold">- OR -</div>
            <input type="text" name="image_path" value="{{ $character->image_path ?? '' }}" 
                class="w-full border rounded px-3 py-2 text-sm bg-gray-50" 
                placeholder="https:// 외부 이미지 주소를 입력하세요 (파일 업로드 시 무시됨)">
            @if($character && $character->image_path)
                <div class="mt-1 text-xs text-blue-600">
                    현재 파일: <a href="{{ $character->image_path }}" target="_blank" class="underline">{{ basename($character->image_path) }}</a>
                </div>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-sm font-bold text-gray-700 mb-1">전신 이미지</label>
            <input type="file" name="image_path2" class="w-full border rounded px-3 py-2 bg-white">
            <div class="text-center text-xs text-gray-400 font-bold">- OR -</div>
            <input type="text" name="image_path2" value="{{ $character->image_path2 ?? '' }}" 
                class="w-full border rounded px-3 py-2 text-sm bg-gray-50" 
                placeholder="https:// 외부 이미지 주소를 입력하세요 (파일 업로드 시 무시됨)">
            @if($character && $character->image_path2)
                <div class="mt-1 text-xs text-blue-600">
                    현재 파일: <a href="{{ $character->image_path2 }}" target="_blank" class="underline">{{ basename($character->image_path2) }}</a>
                </div>
            @endif
        </div>

    @if($group->use_fixed_char_fields)
        <div class="mb-6 bg-gray-50 p-4 rounded border">
            @php
                $fixedFields = $group->char_fixed_fields ? json_decode($group->char_fixed_fields, true) : [];
                
                $savedDataMap = [];
                if(!empty($profile)) {
                    foreach($profile as $item) {
                        $savedDataMap[$item['key']] = $item['value'];
                    }
                }
            @endphp

            @foreach($fixedFields as $index => $field)
                @php 
                    if($field['type'] == 'select'){
                        $field['options'] = explode("," , $field['options']);
                    }
                    $val = $savedDataMap[$field['name']] ?? ''; 
                @endphp
                
                <div class="mb-4">
                    <label class="block text-sm font-bold text-gray-700 mb-1">
                        {{ $field['name'] }}
                        @if($field['required']) <span class="text-red-500">*</span> @endif
                    </label>
                    
                    <input type="hidden" name="fixed_data[{{ $index }}][key]" value="{{ $field['name'] }}">
                    <input type="hidden" name="fixed_data[{{ $index }}][type]" value="{{ $field['type'] }}">

                    @if($field['type'] === 'text')
                        <input type="text" name="fixed_data[{{ $index }}][value]" value="{{ $val }}" 
                            class="w-full border rounded px-3 py-2" {{ $field['required'] ? 'required' : '' }}>

                    @elseif($field['type'] === 'textarea')
                        <textarea name="fixed_data[{{ $index }}][value]" class="w-full border rounded px-3 py-2 h-20 resize-none" 
                            {{ $field['required'] ? 'required' : '' }}>{{ $val }}</textarea>
                    @elseif($field['type'] === 'select')
                        <select name="fixed_data[{{ $index }}][value]" {{ $field['required'] ? 'required' : '' }}>
                            @foreach( $field['options'] as $value )
                                <option value="{{ $value }}" {{ $val == $value ? "selected" : "" }}>{{ $value }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
            @endforeach
        </div>

    @else
        <div x-data="{ 
            stats: {{ $mode === 'edit' && !empty($profile) ? str_replace('"', "'", json_encode($profile, JSON_UNESCAPED_UNICODE)) : "[{key: '', value: ''}]" }},
            addStat() { this.stats.push({key: '', value: ''}); },
            removeStat(index) { this.stats.splice(index, 1); }
        }" class="mb-6 bg-gray-50 p-4 rounded border">
            
            <label class="block text-sm font-bold text-gray-700 mb-2">상세 정보 (자유 설정)</label>
            <template x-for="(stat, index) in stats" :key="index">
                <div class="flex space-x-2 mb-2">
                    <input type="text" :name="`profile[${index}][key]`" x-model="stat.key" class="w-1/3 border rounded px-2 py-1 text-sm" placeholder="항목">
                    <input type="text" :name="`profile[${index}][value]`" x-model="stat.value" class="flex-1 border rounded px-2 py-1 text-sm" placeholder="내용">
                    <button type="button" @click="removeStat(index)" class="text-red-500 hover:text-red-700 px-2">×</button>
                </div>
            </template>
            <button type="button" @click="addStat()" class="mt-2 text-sm text-blue-600 hover:underline font-bold">+ 항목 추가하기</button>
        </div>
    @endif

        <div class="flex justify-end space-x-2 border-t pt-4">
            <a href="" class="px-4 py-2 border rounded text-gray-600 hover:bg-gray-50">취소</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">
                {{ $mode === 'edit' ? '수정완료' : '생성완료' }}
            </button>
        </div>
    </form>
</div>
@endsection