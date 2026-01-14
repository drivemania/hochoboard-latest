@extends($themeLayout)

@section('content')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <div class="border-b pb-4 mb-6">
        <h2 class="text-2xl font-bold text-gray-800">{{ $board->title }} - 글쓰기</h2>
    </div>

    <form action="" method="POST" onsubmit="return confirm('등록하시겠습니까?');">
        
        <div class="flex items-center space-x-6 mb-4">
            @if(isset($_SESSION['level']) && $_SESSION['level'] >= 10) 
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_notice" value="1" class="w-4 h-4 text-blue-600 rounded">
                <span class="ml-2 text-sm text-gray-700 font-bold">📢 공지사항</span>
            </label>
            @endif

            @if($board->use_secret)
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_secret" value="1" class="w-4 h-4 text-red-500 rounded">
                <span class="ml-2 text-sm text-gray-700">🔒 비밀글</span>
            </label>
            @endif
        </div>

        <div class="mb-4">
            <input type="text" name="subject" class="w-full text-lg border-b-2 border-gray-200 py-2 focus:outline-none focus:border-blue-600 transition placeholder-gray-400" placeholder="제목을 입력하세요" required>
        </div>

        @php
            $customFields = $board->custom_fields ? json_decode($board->custom_fields, true) : [];
        @endphp

        @if(!empty($customFields))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-gray-50 p-4 rounded border">
            @foreach($customFields as $field)
                <div class="{{ $field['type'] === 'textarea' ? 'col-span-1 md:col-span-2' : '' }}">
                    <label class="block text-sm font-bold text-gray-700 mb-1">
                        {{ $field['name'] }}
                        @if(!empty($field['required'])) <span class="text-red-500">*</span> @endif
                    </label>

                    @if($field['type'] === 'text')
                        <input type="text" name="custom[{{ $field['name'] }}]" class="w-full border rounded px-3 py-2" {{ !empty($field['required']) ? 'required' : '' }}>
                    
                    @elseif($field['type'] === 'date')
                        <input type="date" name="custom[{{ $field['name'] }}]" class="w-full border rounded px-3 py-2" {{ !empty($field['required']) ? 'required' : '' }}>

                    @elseif($field['type'] === 'textarea')
                        <textarea name="custom[{{ $field['name'] }}]" class="w-full border rounded px-3 py-2 h-20 resize-none" {{ !empty($field['required']) ? 'required' : '' }}></textarea>

                    @elseif($field['type'] === 'select')
                        @php $options = explode(',', $field['options']); @endphp
                        <select name="custom[{{ $field['name'] }}]" class="w-full border rounded px-3 py-2 bg-white" {{ !empty($field['required']) ? 'required' : '' }}>
                            <option value="">선택하세요</option>
                            @foreach($options as $opt)
                                <option value="{{ trim($opt) }}">{{ trim($opt) }}</option>
                            @endforeach
                        </select>

                    @elseif($field['type'] === 'checkbox')
                        @php $options = explode(',', $field['options']); @endphp
                        <div class="flex flex-wrap gap-3 mt-2">
                            @foreach($options as $opt)
                                <label class="flex items-center space-x-1 cursor-pointer">
                                    <input type="checkbox" name="custom[{{ $field['name'] }}][]" value="{{ trim($opt) }}" class="w-4 h-4 text-blue-600 rounded">
                                    <span class="text-sm">{{ trim($opt) }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        <div class="mb-6">
            <textarea name="content" id="editor" class="w-full h-80 border rounded-lg p-4 focus:outline-none focus:ring-2 focus:ring-blue-200 resize-none" placeholder="내용을 입력하세요"></textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ $currentUrl }}" class="px-5 py-2 rounded border border-gray-300 text-gray-600 hover:bg-gray-50 transition">취소</a>
            <button type="submit" class="px-5 py-2 rounded bg-blue-600 text-white font-bold hover:bg-blue-700 transition shadow-md">등록하기</button>
        </div>
    </form>
</div>

@if($board->use_editor)
@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            ClassicEditor
                .create(document.querySelector('#editor'), {
                    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'undo', 'redo'],
                    language: 'ko'
                })
                .then(editor => {
                    const editorElement = editor.ui.view.editable.element;
                    editorElement.style.height = '400px';
                })
                .catch(error => {
                    console.error(error);
                });
        });
    </script>
@endpush
@endif
    <style>
        .ck-editor__editable { min-height: 400px; }
    </style>
@endsection