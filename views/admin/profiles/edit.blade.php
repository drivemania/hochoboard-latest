@extends('layouts.admin')
@section('title', '프로필 양식 설정 - ' . $group->name)
@section('header', $group->name . ' : 프로필 양식 설정')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="{{ $base_path }}/admin/profiles" class="text-gray-500 hover:text-blue-600 text-sm font-bold">← 그룹 목록으로 돌아가기</a>
    </div>

    <form action="{{ $base_path }}/admin/profiles/update" method="POST">
        <input type="hidden" name="id" value="{{ $group->id }}">

        <div class="bg-white p-6 rounded-lg shadow-sm mb-6 border border-gray-200">
            <h3 class="text-lg font-bold border-b pb-2 mb-4 flex justify-between items-center">
                <span>🧙‍♂️ 프로필 양식 설정</span>
            </h3>

            <div class="mb-6 bg-gray-50 p-4 rounded border">
                <label class="font-bold text-gray-700 block mb-2">프로필 입력 방식</label>
                <div class="flex space-x-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="use_fixed_char_fields" value="0" class="w-4 h-4 text-blue-600" 
                            {{ !$group->use_fixed_char_fields ? 'checked' : '' }}>
                        <span class="ml-2">자유 입력 (회원이 직접 항목 추가)</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="use_fixed_char_fields" value="1" class="w-4 h-4 text-blue-600"
                            {{ $group->use_fixed_char_fields ? 'checked' : '' }}>
                        <span class="ml-2">관리자 지정 양식 (아래 설정한 항목만 입력 가능)</span>
                    </label>
                </div>
            </div>

            <div x-data="{ 
                fields: {{ $group->char_fixed_fields ? $group->char_fixed_fields : '[]' }},
                addField() {
                    this.fields.push({ name: '', type: 'text', required: 0, options: '' });
                },
                removeField(index) {
                    if(confirm('이 항목을 삭제하시겠습니까?')) {
                        this.fields.splice(index, 1);
                    }
                }
            }">
                <div class="space-y-3">
                    <template x-for="(field, index) in fields" :key="index">
                        <div class="flex flex-wrap items-start gap-2 p-3 bg-gray-50 rounded border transition-all duration-200">
                            
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-xs text-gray-500 mb-1">항목명</label>
                                <input type="text" :name="`char_fields[${index}][name]`" x-model="field.name" class="w-full border rounded px-2 py-1 text-sm" placeholder="예: 혈액형, 진영" required>
                            </div>

                            <div class="w-32">
                                <label class="block text-xs text-gray-500 mb-1">타입</label>
                                <select :name="`char_fields[${index}][type]`" x-model="field.type" class="w-full border rounded px-2 py-1 text-sm bg-white">
                                    <option value="text">한줄 텍스트</option>
                                    <option value="textarea">여러줄 텍스트</option>
                                    <option value="select">선택 박스 (▼)</option>
                                    {{-- <option value="file">파일 첨부</option> --}}
                                </select>
                            </div>

                            <div class="w-16 text-center pt-5">
                                <label class="inline-flex items-center cursor-pointer">
                                    <input type="checkbox" :name="`char_fields[${index}][required]`" x-model="field.required" value="1" class="w-4 h-4 text-blue-600 rounded">
                                    <span class="ml-1 text-xs text-gray-600">필수</span>
                                </label>
                            </div>

                            <div class="pt-5">
                                <button type="button" @click="removeField(index)" class="text-red-500 font-bold text-sm hover:text-red-700">×</button>
                            </div>

                            <div class="w-full mt-2 border-t pt-2" x-show="field.type === 'select'" x-transition>
                                <label class="block text-xs font-bold text-indigo-600 mb-1">
                                    💡 선택지 입력 (쉼표 <code class="bg-gray-200 px-1 rounded">,</code>로 구분)
                                </label>
                                <input type="text" 
                                       :name="`char_fields[${index}][options]`" 
                                       x-model="field.options" 
                                       class="w-full border border-indigo-300 rounded px-2 py-1 text-sm bg-indigo-50 placeholder-indigo-300" 
                                       placeholder="성별이나 진영 등을 선택할 수 있습니다.">
                            </div>

                        </div>
                    </template>
                </div>

                <div class="mt-4">
                    <button type="button" @click="addField()" class="w-full py-3 border-2 border-dashed border-gray-300 rounded text-gray-500 hover:text-blue-600 hover:border-blue-400 font-bold transition">
                        + 양식 항목 추가하기
                    </button>
                </div>
            </div>
        </div>

        <div class="text-right">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700 shadow-lg">
                설정 저장하기
            </button>
        </div>
    </form>
</div>
@endsection