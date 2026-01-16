@extends('layouts.admin')
@section('title', '게시판 설정 - ' . $board->title)
@section('header', '게시판 상세 설정')

@if($board->type === 'page')
@section('content')

<form action="{{ $base_path }}/admin/boards/update" method="POST">
    <input type="hidden" name="id" value="{{ $board->id }}">

    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <h3 class="text-lg font-bold border-b pb-2 mb-4">🛠 기본 설정</h3>
        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">게시판 종류</label>
            <div class="flex space-x-6">
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="document" class="w-4 h-4 text-blue-600" 
                        {{ ($board->type ?? 'document') == 'document' ? 'checked' : '' }} disabled>
                    <span class="ml-2">일반 게시판</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="character" class="w-4 h-4 text-green-600"
                        {{ ($board->type ?? '') == 'character' ? 'checked' : '' }} disabled>
                    <span class="ml-2">캐릭터 게시판</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="load" class="w-4 h-4 text-green-600"
                        {{ ($board->type ?? '') == 'load' ? 'checked' : '' }} disabled>
                    <span class="ml-2">로드비 게시판</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="page" class="w-4 h-4 text-green-600"
                        {{ ($board->type ?? '') == 'page' ? 'checked' : '' }} disabled>
                    <span class="ml-2">페이지</span>
                </label>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold mb-2">게시판 이름</label>
                <input type="text" name="title" value="{{ $board->title }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <h3 class="text-lg font-bold border-b pb-2 mb-4">페이지 설정</h3>
        <div class="flex justify-end mb-2">
            <button type="button" id="toggle-source-btn" 
                    class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded hover:bg-indigo-700 transition">
                &lt;/&gt; HTML 소스 편집
            </button>
        </div>
        <div class="mb-4">
            <textarea id="editor" rows="3" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">{{ $board->notice }}</textarea>
            <textarea name="notice" id="source-textarea">{{ $board->notice }}</textarea>
            <p class="text-xs text-gray-500 mt-1">페이지에 표시될 내용을 작성해주세요.</p>
        </div>
    </div>
    <div class="text-center">
        <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 shadow-lg">
            설정 저장하기
        </button>
    </div>
</form>
<style>
    .ck-editor__editable { min-height: 200px; }
    #source-textarea {
        width: 100%;
        height: 400px;
        background-color: #1e1e1e;
        color: #d4d4d4;
        font-family: 'Consolas', 'Monaco', monospace;
        padding: 1rem;
        border: 1px solid #ccc;
        border-radius: 0.375rem;
        display: none;
        resize: vertical;
        outline: none;
    }
</style>
@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const defaultToHtml = true;

    let myEditor;
    const sourceTextarea = document.getElementById('source-textarea');
    const toggleBtn = document.getElementById('toggle-source-btn');
    const originalTextarea = document.querySelector('#editor'); 
    
    const submitBtn = document.querySelector('button[type="submit"], input[type="image"], #btn_submit, .btn_submit');

    let isSourceMode = defaultToHtml; 

    ClassicEditor
        .create(originalTextarea, {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
                'undo', 'redo'
            ]
        })
        .then(editor => {
            myEditor = editor;
            if (defaultToHtml) {
                editor.ui.view.element.style.display = 'none';
                sourceTextarea.style.display = 'block';
                toggleBtn.innerText = "👁️ 위지윅 에디터로 보기";
                toggleBtn.classList.replace('bg-indigo-600', 'bg-gray-600');
                toggleBtn.classList.replace('hover:bg-indigo-700', 'hover:bg-gray-700');
            }
        })
        .catch(error => {
            console.error(error);
        });

    function syncSourceToEditor() {
        if (isSourceMode) {
            originalTextarea.value = sourceTextarea.value;
        } else {
            const data = myEditor.getData();
            sourceTextarea.value = data;
            originalTextarea.value = data;
        }
    }

    toggleBtn.addEventListener('click', function() {
        if (!myEditor) return;
        const editorElement = myEditor.ui.view.element;

        if (isSourceMode) {
            syncSourceToEditor();

            sourceTextarea.style.display = 'none';
            editorElement.style.display = 'block';
            
            toggleBtn.innerText = "</> HTML 소스 편집";
            toggleBtn.classList.replace('bg-gray-600', 'bg-indigo-600');
            toggleBtn.classList.replace('hover:bg-gray-700', 'hover:bg-indigo-700');
            
            isSourceMode = false;
        } else {
            const html = myEditor.getData();
            sourceTextarea.value = html;

            editorElement.style.display = 'none';
            sourceTextarea.style.display = 'block';

            toggleBtn.innerText = "👁️ 위지윅 에디터로 보기";
            toggleBtn.classList.replace('bg-indigo-600', 'bg-gray-600');
            toggleBtn.classList.replace('hover:bg-indigo-700', 'hover:bg-gray-700');

            isSourceMode = true;
        }
    });

    if (submitBtn) {
        submitBtn.addEventListener('mouseenter', syncSourceToEditor);
        submitBtn.addEventListener('mousedown', syncSourceToEditor);
    }
    sourceTextarea.addEventListener('blur', syncSourceToEditor);
});
</script>
@endpush
@endsection
@else
@section('content')
<form action="{{ $base_path }}/admin/boards/update" method="POST">
    <input type="hidden" name="id" value="{{ $board->id }}">

    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <h3 class="text-lg font-bold border-b pb-2 mb-4">🛠 기본 설정</h3>
        <div class="mb-4">
            <label class="block text-sm font-bold mb-2">게시판 종류</label>
            <div class="flex space-x-6">
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="document" class="w-4 h-4 text-blue-600" 
                        {{ ($board->type ?? 'document') == 'document' ? 'checked' : '' }} disabled>
                    <span class="ml-2">일반 게시판</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="character" class="w-4 h-4 text-green-600"
                        {{ ($board->type ?? '') == 'character' ? 'checked' : '' }} disabled>
                    <span class="ml-2">캐릭터 게시판</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="load" class="w-4 h-4 text-green-600"
                        {{ ($board->type ?? '') == 'load' ? 'checked' : '' }} disabled>
                    <span class="ml-2">로드비 게시판</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="type" value="page" class="w-4 h-4 text-green-600"
                        {{ ($board->type ?? '') == 'page' ? 'checked' : '' }} disabled>
                    <span class="ml-2">페이지</span>
                </label>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold mb-2">게시판 이름</label>
                <input type="text" name="title" value="{{ $board->title }}" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold mb-2">페이지당 글 수</label>
                <input type="number" name="list_count" value="{{ $board->list_count }}" class="w-full border rounded px-3 py-2">
            </div>
        </div>
        @if($board->type === 'document')
        <div class="mt-4 p-4 border rounded bg-gray-50">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="use_editor" value="1" class="w-5 h-5 text-blue-600 rounded" 
                    {{ $board->use_editor ? 'checked' : '' }}>
                <span class="ml-2 font-bold text-gray-700">📝 위지윅 에디터 사용</span>
            </label>
            <p class="text-xs text-gray-500 mt-1 ml-7">체크하면 글쓰기 시 CKEditor 5가 적용됩니다. (스킨에 따라 지원하지 않을 수도 있어요.)</p>
        </div>
        <div class="mt-4 p-4 border rounded bg-gray-50">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="use_secret" value="1" class="w-5 h-5 text-blue-600 rounded" 
                    {{ $board->use_secret ? 'checked' : '' }}>
                <span class="ml-2 font-bold text-gray-700">🔒 비밀글 사용</span>
            </label>
        </div>
        @endif
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <h3 class="text-lg font-bold border-b pb-2 mb-4">🔒 권한 설정</h3>
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <label class="block text-sm font-bold mb-2 text-gray-600">목록 읽기 권한</label>
                <input type="number" name="read_level" value="{{ $board->read_level }}" class="w-20 border rounded px-2 py-1 text-center mx-auto">
                <div class="text-xs text-gray-400 mt-1">0:손님, 1:회원, 10:관리자</div>
            </div>
            <div>
                <label class="block text-sm font-bold mb-2 text-gray-600">글 쓰기 권한</label>
                <input type="number" name="write_level" value="{{ $board->write_level }}" class="w-20 border rounded px-2 py-1 text-center mx-auto">
            </div>
            <div>
                <label class="block text-sm font-bold mb-2 text-gray-600">댓글 쓰기 권한</label>
                <input type="number" name="comment_level" value="{{ $board->comment_level }}" class="w-20 border rounded px-2 py-1 text-center mx-auto">
            </div>
            </div>
    </div>
    @if(in_array($board->type, array('document', 'load')))
    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <h3 class="text-lg font-bold border-b pb-2 mb-4">상단 공지 설정</h3>
        <div class="flex justify-end mb-2">
            <button type="button" id="toggle-source-btn" 
                    class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded hover:bg-indigo-700 transition">
                &lt;/&gt; HTML 소스 편집
            </button>
        </div>

        <div class="mb-4">
            <textarea id="editor" rows="3" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">{{ $board->notice }}</textarea>
            <textarea name="notice" id="source-textarea">{{ $board->notice }}</textarea>
            <p class="text-xs text-gray-500 mt-1">게시판 상단에 표시될 공지를 작성해주세요.</p>
        </div>
    </div>
    @endif

    <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
        <h3 class="text-lg font-bold border-b pb-2 mb-4">🎨 게시판 디자인 (스킨)</h3>
        @if($board->type === 'document')
            <div x-data="{ selectedSkin: '{{ $board->board_skin }}' }" class="grid grid-cols-6 md:grid-cols-4 gap-4">
                <input type="hidden" name="board_skin" :value="selectedSkin">
                @foreach($boardSkins as $skin)
                <div @click="selectedSkin = '{{ $skin['id'] }}'" 
                    class="cursor-pointer border-2 rounded-lg overflow-hidden relative"
                    :class="selectedSkin == '{{ $skin['id'] }}' ? 'border-blue-500 ring-2' : 'border-gray-200'">

                    <div class="p-2 text-center text-sm font-bold">{{ $skin['name'] }}</div>
                </div>
                @endforeach
            </div>
        @elseif($board->type === 'character')
            <div x-data="{ selectedSkin: '{{ $board->board_skin }}' }" class="grid grid-cols-6 md:grid-cols-4 gap-4">
                <input type="hidden" name="board_skin" :value="selectedSkin">
                @foreach($charSkins as $skin)
                <div @click="selectedSkin = '{{ $skin['id'] }}'" 
                    class="cursor-pointer border-2 rounded-lg overflow-hidden relative"
                    :class="selectedSkin == '{{ $skin['id'] }}' ? 'border-blue-500 ring-2' : 'border-gray-200'">

                    <div class="p-2 text-center text-sm font-bold">{{ $skin['name'] }}</div>
                </div>
                @endforeach
            </div>
        @elseif($board->type === 'load')
            <div x-data="{ selectedSkin: '{{ $board->board_skin }}' }" class="grid grid-cols-6 md:grid-cols-4 gap-4">
                <input type="hidden" name="board_skin" :value="selectedSkin">
                @foreach($loadSkins as $skin)
                <div @click="selectedSkin = '{{ $skin['id'] }}'" 
                    class="cursor-pointer border-2 rounded-lg overflow-hidden relative"
                    :class="selectedSkin == '{{ $skin['id'] }}' ? 'border-blue-500 ring-2' : 'border-gray-200'">

                    <div class="p-2 text-center text-sm font-bold">{{ $skin['name'] }}</div>
                </div>
                @endforeach
            </div>
        @endif
        
    </div>
    @if($board->type === 'document')
    <div class="bg-white p-6 rounded-lg shadow-sm mb-6 border border-gray-200">
        <h3 class="text-lg font-bold border-b pb-2 mb-4 flex justify-between items-center">
            <span>🛠 사용자 정의 필드 (확장 필드)</span>
            <span class="text-xs font-normal text-gray-500">텍스트, 선택박스 등을 자유롭게 추가하세요.</span>
        </h3>

        <div x-data="{ 
            fields: {{ $board->custom_fields ? $board->custom_fields : '[]' }},
            addField() {
                this.fields.push({ name: '', type: 'text', required: 0, options: '' });
            },
            removeField(index) {
                if(confirm('이 필드를 삭제하시겠습니까?')) {
                    this.fields.splice(index, 1);
                }
            }
        }">
            
            <div class="space-y-3">
                <template x-for="(field, index) in fields" :key="index">
                    <div class="flex flex-wrap items-start gap-2 p-3 bg-gray-50 rounded border">
                        
                        <div class="flex-1">
                            <label class="block text-xs text-gray-500 mb-1">필드 이름 (예: 연락처)</label>
                            <input type="text" :name="`custom_fields[${index}][name]`" x-model="field.name" class="w-full border rounded px-2 py-1 text-sm" placeholder="필드명" required>
                        </div>

                        <div class="w-32">
                            <label class="block text-xs text-gray-500 mb-1">입력 타입</label>
                            <select :name="`custom_fields[${index}][type]`" x-model="field.type" class="w-full border rounded px-2 py-1 text-sm bg-white">
                                <option value="text">한줄 입력 (Text)</option>
                                <option value="textarea">여러줄 입력 (Textarea)</option>
                                <option value="select">선택 박스 (Select)</option>
                                <option value="checkbox">체크 박스 (Checkbox)</option>
                                <option value="date">날짜 (Date)</option>
                            </select>
                        </div>

                        <div class="flex-1" x-show="field.type === 'select' || field.type === 'checkbox'">
                            <label class="block text-xs text-gray-500 mb-1">선택 옵션 (콤마 , 로 구분)</label>
                            <input type="text" :name="`custom_fields[${index}][options]`" x-model="field.options" class="w-full border rounded px-2 py-1 text-sm" placeholder="예: 사과,배,포도">
                        </div>

                        <div class="w-16 text-center pt-5">
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" :name="`custom_fields[${index}][required]`" x-model="field.required" value="1" class="w-4 h-4 text-blue-600 rounded">
                                <span class="ml-1 text-xs text-gray-600">필수</span>
                            </label>
                        </div>

                        <div class="pt-5">
                            <button type="button" @click="removeField(index)" class="text-red-500 hover:text-red-700 text-sm font-bold">×</button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="mt-4">
                <button type="button" @click="addField()" class="w-full py-2 border-2 border-dashed border-gray-300 rounded text-gray-500 hover:border-blue-500 hover:text-blue-600 font-bold transition">
                    + 필드 추가하기
                </button>
            </div>

            <p class="text-xs text-gray-400 mt-2">
                ※ '선택 옵션'은 타입이 '선택 박스'나 '체크 박스'일 때만 입력하면 됩니다.<br>
                ※ 순서대로 출력됩니다.
            </p>
        </div>
    </div>
    @endif

    <div class="text-center">
        <button type="submit" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 shadow-lg">
            설정 저장하기
        </button>
    </div>
</form>
<style>
    .ck-editor__editable { min-height: 200px; }
    #source-textarea {
        width: 100%;
        height: 400px;
        background-color: #1e1e1e;
        color: #d4d4d4;
        font-family: 'Consolas', 'Monaco', monospace;
        padding: 1rem;
        border: 1px solid #ccc;
        border-radius: 0.375rem;
        display: none;
        resize: vertical;
        outline: none;
    }
</style>
@push('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const defaultToHtml = true;

    let myEditor;
    const sourceTextarea = document.getElementById('source-textarea');
    const toggleBtn = document.getElementById('toggle-source-btn');
    const originalTextarea = document.querySelector('#editor'); 
    
    const submitBtn = document.querySelector('button[type="submit"], input[type="image"], #btn_submit, .btn_submit');

    let isSourceMode = defaultToHtml; 

    ClassicEditor
        .create(originalTextarea, {
            toolbar: [
                'heading', '|',
                'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|',
                'undo', 'redo'
            ]
        })
        .then(editor => {
            myEditor = editor;
            if (defaultToHtml) {
                editor.ui.view.element.style.display = 'none';
                sourceTextarea.style.display = 'block';
                toggleBtn.innerText = "👁️ 위지윅 에디터로 보기";
                toggleBtn.classList.replace('bg-indigo-600', 'bg-gray-600');
                toggleBtn.classList.replace('hover:bg-indigo-700', 'hover:bg-gray-700');
            }
        })
        .catch(error => {
            console.error(error);
        });

    function syncSourceToEditor() {
        if (isSourceMode) {
            originalTextarea.value = sourceTextarea.value;
        } else {
            const data = myEditor.getData();
            sourceTextarea.value = data;
            originalTextarea.value = data;
        }
    }

    toggleBtn.addEventListener('click', function() {
        if (!myEditor) return;
        const editorElement = myEditor.ui.view.element;

        if (isSourceMode) {
            syncSourceToEditor();

            sourceTextarea.style.display = 'none';
            editorElement.style.display = 'block';
            
            toggleBtn.innerText = "</> HTML 소스 편집";
            toggleBtn.classList.replace('bg-gray-600', 'bg-indigo-600');
            toggleBtn.classList.replace('hover:bg-gray-700', 'hover:bg-indigo-700');
            
            isSourceMode = false;
        } else {
            const html = myEditor.getData();
            sourceTextarea.value = html;

            editorElement.style.display = 'none';
            sourceTextarea.style.display = 'block';

            toggleBtn.innerText = "👁️ 위지윅 에디터로 보기";
            toggleBtn.classList.replace('bg-indigo-600', 'bg-gray-600');
            toggleBtn.classList.replace('hover:bg-indigo-700', 'hover:bg-gray-700');

            isSourceMode = true;
        }
    });

    if (submitBtn) {
        submitBtn.addEventListener('mouseenter', syncSourceToEditor);
        submitBtn.addEventListener('mousedown', syncSourceToEditor);
    }
    sourceTextarea.addEventListener('blur', syncSourceToEditor);
});
</script>
@endpush
@endsection

@endif

