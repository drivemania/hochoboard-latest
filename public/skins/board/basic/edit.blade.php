@extends($themeLayout)

@section('content')

<div class="max-w-4xl mx-auto bg-white p-6 rounded-lg shadow-sm border border-neutral-200">
    <div class="border-b pb-4 mb-6">
        <h2 class="text-2xl font-bold text-neutral-800">{{ $board->title }} - 글 수정</h2>
    </div>

    <form action="" method="POST">
        
        <div class="flex items-center space-x-6 mb-4">
            @if(isset($_SESSION['level']) && $_SESSION['level'] >= 10) 
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_notice" value="1" class="w-4 h-4 text-amber-600 rounded" {{ $document->is_notice ? 'checked' : '' }}>
                <span class="ml-2 text-sm text-neutral-700 font-bold">📢 공지사항</span>
            </label>
            @endif

            @if($board->use_secret)
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="is_secret" value="1" class="w-4 h-4 text-red-500 rounded" {{ $document->is_secret ? 'checked' : '' }}>
                <span class="ml-2 text-sm text-neutral-700">🔒 비밀글</span>
            </label>
            @endif
        </div>

        <div class="mb-4">
            <input type="text" name="subject" value="{{ $document->title }}" class="w-full text-lg border-b-2 border-neutral-200 py-2 focus:outline-none focus:border-amber-600 transition" required>
        </div>

        <div class="mb-6">
            <textarea name="content" id="editor" class="w-full h-80 border rounded-lg p-4 focus:outline-none focus:ring-2 focus:ring-amber-200 resize-none" required>{{ $document->content }}</textarea>
        </div>

        @php
            $customFields = $board->custom_fields ? json_decode($board->custom_fields, true) : [];
            $savedData = $document->custom_data ? json_decode($document->custom_data, true) : [];
        @endphp

        @if(!empty($customFields))
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 bg-neutral-50 p-4 rounded border">
            @foreach($customFields as $field)
                @php
                    $val = $savedData[$field['name']] ?? '';
                @endphp

                <div class="{{ $field['type'] === 'textarea' ? 'col-span-1 md:col-span-2' : '' }}">
                    <label class="block text-sm font-bold text-neutral-700 mb-1">
                        {{ $field['name'] }}
                        @if(!empty($field['required'])) <span class="text-red-500">*</span> @endif
                    </label>

                    @if($field['type'] === 'text')
                        <input type="text" name="custom[{{ $field['name'] }}]" value="{{ $val }}" class="w-full border rounded px-3 py-2">
                    
                    @elseif($field['type'] === 'date')
                        <input type="date" name="custom[{{ $field['name'] }}]" value="{{ $val }}" class="w-full border rounded px-3 py-2">

                    @elseif($field['type'] === 'textarea')
                        <textarea name="custom[{{ $field['name'] }}]" class="w-full border rounded px-3 py-2 h-20 resize-none">{{ $val }}</textarea>

                    @elseif($field['type'] === 'select')
                        @php $options = explode(',', $field['options']); @endphp
                        <select name="custom[{{ $field['name'] }}]" class="w-full border rounded px-3 py-2 bg-white">
                            <option value="">선택하세요</option>
                            @foreach($options as $opt)
                                <option value="{{ trim($opt) }}" {{ $val == trim($opt) ? 'selected' : '' }}>{{ trim($opt) }}</option>
                            @endforeach
                        </select>

                    @elseif($field['type'] === 'checkbox')
                        @php 
                            $options = explode(',', $field['options']); 
                            $checkedArr = explode(',', $val);
                        @endphp
                        <div class="flex flex-wrap gap-3 mt-2">
                            @foreach($options as $opt)
                                <label class="flex items-center space-x-1 cursor-pointer">
                                    <input type="checkbox" name="custom[{{ $field['name'] }}][]" value="{{ trim($opt) }}" 
                                        class="w-4 h-4 text-amber-600 rounded"
                                        {{ in_array(trim($opt), $checkedArr) ? 'checked' : '' }}>
                                    <span class="text-sm">{{ trim($opt) }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @endif

        <div class="flex justify-end space-x-2">
            <a href="javascript:history.back()" class="px-5 py-2 rounded border border-neutral-300 text-neutral-600 hover:bg-neutral-50 transition">취소</a>
            <button type="submit" class="px-5 py-2 rounded bg-amber-600 text-white font-bold hover:bg-amber-700 transition shadow-md">수정완료</button>
        </div>
    </form>
</div>
<style>
    .ck-editor__editable { min-height: 400px; }
</style>

@if($board->use_editor)
@push('scripts')
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
    <script>
        class MyUploadAdapter {
            constructor(loader) {
                this.loader = loader;
            }
            upload() {
                return this.loader.file
                    .then(file => new Promise((resolve, reject) => {
                        this._initRequest();
                        this._initListeners(resolve, reject, file);
                        this._sendRequest(file);
                    }));
            }
    
            abort() {
                if (this.xhr) {
                    this.xhr.abort();
                }
            }
    
            _initRequest() {
                const xhr = this.xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ $base_path }}/image/upload', true); 
                xhr.responseType = 'json';
            }
    
            _initListeners(resolve, reject, file) {
                const xhr = this.xhr;
                const loader = this.loader;
                const genericErrorText = `파일 업로드 실패: ${file.name}`;
    
                xhr.addEventListener('error', () => reject(genericErrorText));
                xhr.addEventListener('abort', () => reject());
                xhr.addEventListener('load', () => {
                    const response = xhr.response;
    
                    if (!response || response.error) {
                        return reject(response && response.error ? response.error.message : genericErrorText);
                    }
    
                    resolve({
                        default: response.url
                    });
                });
    
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', evt => {
                        if (evt.lengthComputable) {
                            loader.uploadTotal = evt.total;
                            loader.uploaded = evt.loaded;
                        }
                    });
                }
            }
    
            _sendRequest(file) {
                const data = new FormData();
                data.append('upload', file);
                this.xhr.send(data);
            }
        }
    
        function MyCustomUploadAdapterPlugin(editor) {
            editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
                return new MyUploadAdapter(loader);
            };
        }
    
        ClassicEditor
            .create(document.querySelector('#editor'), {
                extraPlugins: [MyCustomUploadAdapterPlugin],
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'uploadImage'],
            })
            .then(editor => {
            })
            .catch(error => {
            });
    </script>
@endpush
@endif

@endsection