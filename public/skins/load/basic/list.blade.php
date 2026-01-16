@extends($themeLayout)

@section('content')

@push('styles')
<script src="https://cdn.tailwindcss.com"></script>
@endpush

@php
$searchTarget = $_GET['search_target'] ?? "";
$keyword = $_GET['keyword'] ?? "";
if(isset($_SESSION['user_idx'])){
    $myCharacter = Helper::getMyMainChr($_SESSION['user_idx'], $group->id);
}
@endphp

<div class="max-w-5xl mx-auto px-4 py-8 relative" x-data="{ writeModal: false, showComments: false }">
    @if($board->notice != null)
    <div class="space-y-8 mb-8">
        <div class="px-5 py-4 flex justify-between items-center border border-gray-100 text-center">
            <div class="w-full text-center">
                {!! $board->notice !!}
            </div>
        </div>
    </div>
    @endif
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <div>
            <button @click="writeModal = true" type="button" class="border border-gray-200 hover:border-gray-400 px-4 py-2 rounded-md transition-colors text-sm font-bold">업로드</button>
            <button type="button" onclick="window.open('{{ $base_path }}/emoticon', 'emoticon_pop', 'width=400,height=500,scrollbars=yes,resizable=no');"
             class="border border-gray-200 hover:border-gray-400 px-4 py-2 rounded-md transition-colors text-sm font-bold">이모티콘</button>
             <button type="button" onclick="location.reload(true)" class="border border-gray-200 hover:border-gray-400 px-4 py-2 rounded-md transition-colors text-sm font-bold">새로고침</button>

        </div>
        <form class="flex w-full md:w-auto bg-white border border-gray-200 rounded-full px-4 py-2 shadow-sm focus-within:ring-2 focus-within:ring-indigo-100 focus-within:border-indigo-300 transition-all">
            <select name="search_target" class="text-sm text-gray-500 bg-transparent border-none outline-none mr-2">
                <option value="character" {{ $searchTarget == "character" ? "selected" : "" }}>캐릭터</option>
                <option value="member" {{ $searchTarget == "member" ? "selected" : "" }}>멤버</option>
                <option value="anchor" {{ $searchTarget == "anchor" ? "selected" : "" }}>글번호</option>
                <option value="hashtag" {{ $searchTarget == "hashtag" ? "selected" : "" }}>해시태그</option>
            </select>
            <input type="text" name="keyword" placeholder="검색..." class="flex-1 text-m outline-none text-gray-700 placeholder-gray-400 bg-transparent" value="{{ $keyword }}">
            <button type="submit" class="text-gray-400 hover:text-indigo-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </button>
        </form>
    </div>

    @if($documents->lastPage() > 1)
    <div class="mb-6 flex justify-center space-x-1">
        @for($i = 1; $i <= $documents->lastPage(); $i++)
            <a href="?page={{ $i }}" 
               class="px-3 py-1 rounded border {{ $documents->currentPage() == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
               {{ $i }}
            </a>
        @endfor
    </div>
    @endif

    <div class="space-y-8">
        @forelse($documents as $doc)
        <article class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden" x-data="{ showComments: true }">

            <div class="px-5 py-4 flex justify-between items-center border-b border-gray-50">
                <div class="flex items-center gap-3">
                    <h3 class="text-md font-bold text-gray-800 mb-3">#{{ $doc->doc_num }}</h3>
                    <div class="w-10 h-10 rounded-full bg-indigo-50 overflow-hidden text-indigo-600 flex items-center justify-center font-bold text-m border border-indigo-100">
                        @if(!empty($doc->char_image))
                        <img class="w-full h-full object-cover" src="{{ $doc->char_image }}">
                        @else
                        <span class="text-indigo-600 font-bold text-m">{{ mb_substr($doc->nickname, 0, 1) }}</span>
                        @endif
                    </div>
                    <div>
                        <div class="font-bold text-gray-900 text-m">
                            @if(!empty($doc->char_id))
                            <a href="{{ $base_path }}/{{ $doc->char_menu_slug }}/{{ $doc->char_id }}" target="_blank">{{ $doc->char_name }}</a>
                            @endif
                            <a href="#" onclick="window.open('{{ $base_path }}/memo/write?to_id={{ $doc->user_id }}', 'memo', 'width=650,height=700'); return false;">[{{ $doc->nickname }}]</a>
                        </div>
                        <div class="text-sm text-gray-400">{{ date('Y.m.d H:i', strtotime($doc->created_at)) }}</div>
                    </div>
                </div>

                @if(($_SESSION['user_idx'] ?? 0) == $doc->user_id || $_SESSION['level'] === 10)
                <div class="flex items-center gap-2 text-sm text-gray-400">
                    <button type="button" @click="copyText('{{ $_SERVER['SERVER_NAME']  }}{{ $currentUrl }}/{{ $doc->doc_num }}')">공유</button>
                    <span class="text-gray-300">|</span>
                    <form action="{{ $currentUrl }}/{{ $doc->doc_num }}/delete" method="POST" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                        <button type="submit" class="hover:text-red-500">삭제</button>
                    </form>
                </div>
                @endif
            </div>

            <div class="p-5">
                <div class="prose max-w-none text-gray-700 break-all">
                    <style>.view-content img { max-width: fill-available; height: auto; margin-bottom: 1rem;}</style>
                    <div class="view-content grid place-items-center">
                        <a href="{{ $base_path }}{{ $doc->content }}" target="_blank"><img src="{{ $base_path }}{{ $doc->content }}"></a>
                    </div>
                </div>
            </div>

            <div class="px-5 py-3 border-t border-gray-100 flex items-center gap-4">
                <button @click="showComments = !showComments" class="flex items-center gap-1.5 text-gray-500 hover:text-indigo-600 transition-colors text-m font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    <span>댓글 {{ $doc->comment_count }}</span>
                </button>
            </div>

            <div x-show="showComments" x-transition.origin.top class="bg-gray-50 border-t border-gray-100 px-5 py-5 space-y-4">
                
                <ul class="space-y-4">
                    @if(isset($doc->comments) && count($doc->comments) > 0)
                        @foreach($doc->comments as $cmt)
                        <li class="flex gap-3" x-data="{ isEditing: false }">
                            <div class="w-8 h-8 rounded-full overflow-hidden bg-white border border-gray-200 flex-shrink-0 flex items-center justify-center text-sm font-bold text-gray-500">
                                @if(!empty($cmt->char_image))
                                <img class="w-full h-full object-cover" src="{{ $cmt->char_image }}">
                                @else
                                <span class="text-indigo-600 font-bold text-m">{{ mb_substr($cmt->nickname, 0, 1) }}</span>
                                @endif
                            </div>
                            
                            <div class="flex-1">
                                <div x-show="!isEditing">
                                    <div class="bg-white px-4 py-2 rounded-2xl rounded-tl-none border border-gray-200 shadow-sm inline-block max-w-full">
                                        <div class="flex justify-between items-baseline gap-4 mb-1">
                                            <span id="comment_{{ $cmt->id }}" class="scroll-mt-24 target:bg-yellow-50 font-bold text-sm text-gray-800">
                                                @if(!empty($cmt->char_id))
                                                <a href="{{ $base_path }}/{{ $cmt->char_menu_slug }}/{{ $cmt->char_id }}" target="_blank">{{ $cmt->char_name }}</a>
                                                @endif
                                                <a href="#" onclick="window.open('{{ $base_path }}/memo/write?to_id={{ $cmt->user_id }}', 'memo', 'width=650,height=700'); return false;">[{{ $cmt->nickname }}]</a>                    
                                            </span>
                                            <span class="text-xs text-gray-400">{{ date('m.d H:i', strtotime($cmt->created_at)) }}</span>
                                        </div>
                                        {!! $cmt->plugin ?? '' !!}
                                        <p class="text-m text-gray-700 whitespace-pre-wrap leading-relaxed">{!! Helper::auto_hashtag(Helper::auto_link($cmt->content), $currentUrl) !!}</p>
                                    </div>
                                    
                                    @if(($_SESSION['user_idx'] ?? 0) == $cmt->user_id || $_SESSION['level'] === 10)
                                    <div class="mt-1 ml-1 text-[10px] text-gray-400 flex gap-2">
                                        <button @click="isEditing = true" type="button" class="hover:text-indigo-600 underline cursor-pointer">수정</button>
                                        
                                        <span class="text-gray-300">|</span>
                                        
                                        <form action="{{ $base_path }}/comment/delete" method="POST" class="inline-block" onsubmit="return confirm('삭제하시겠습니까?')">
                                            <input type="hidden" name="comment_id" value="{{ $cmt->id }}">
                                            <input type="hidden" name="doc_id" value="{{ $doc->id }}">
                                            <button type="submit" class="hover:text-red-500 underline cursor-pointer">삭제</button>
                                        </form>
                                    </div>
                                    @endif
                                </div>
                        
                                <div x-show="isEditing" style="display: none;">
                                    <form action="{{ $base_path }}/comment/update" method="POST" class="relative mt-1">
                                        <input type="hidden" name="comment_id" value="{{ $cmt->id }}">
                                        <textarea name="content" rows="2" 
                                                  class="w-full bg-white border border-indigo-300 rounded-lg px-3 py-2 text-m outline-none focus:ring-2 focus:ring-indigo-200 transition-all resize-none shadow-sm" 
                                                  required>{{ $cmt->content }}</textarea>
                                        
                                        <div class="flex justify-end gap-2 mt-2">
                                            <button type="button" @click="isEditing = false" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-1.5 border border-gray-300 rounded hover:bg-gray-50 transition">취소</button>
                                            <button type="submit" class="text-sm text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded font-bold shadow-sm transition">저장</button>
                                        </div>
                                    </form>
                                </div>
                        
                            </div>
                        </li>
                        @endforeach
                    @else
                        {{-- <li class="text-center text-m text-gray-400 py-2">아직 댓글이 없습니다. 첫 번째 댓글을 남겨보세요!</li> --}}
                    @endif
                </ul>

                @if(($_SESSION['level'] ?? 0) >= $board->comment_level)
                <form action="{{ $currentUrl }}/{{ $doc->doc_num }}/comment" method="POST" class="flex gap-2 items-start pt-2">
                    <div class="w-8 h-8 rounded-full overflow-hidden bg-indigo-100 flex-shrink-0 overflow-hidden">
                        @if ($myCharacter)
                            <img src="{{ $myCharacter->image_path }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-indigo-500 text-sm">Me</div>
                        @endif
                    </div>
                    <div class="flex-1 relative">
                        <textarea name="content" rows="1" class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 text-m outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-400 transition-all resize-none shadow-sm" placeholder="리플을 작성해주세요." required></textarea>
                        <button type="submit" class="absolute right-2 bottom-1.5 bg-indigo-600 hover:bg-indigo-700 text-white p-1.5 rounded-md transition-colors text-sm font-bold">
                            등록
                        </button>
                    </div>
                </form>
                @endif

            </div>
        </article>
        @empty
        <div class="text-center py-20 bg-gray-50 rounded-2xl border border-gray-100">
            <p class="text-gray-500 font-medium">등록된 로그가 없습니다.</p>
        </div>
        @endforelse
    </div>

    @if($documents->lastPage() > 1)
    <div class="mt-6 flex justify-center space-x-1">
        @for($i = 1; $i <= $documents->lastPage(); $i++)
            <a href="?page={{ $i }}" 
               class="px-3 py-1 rounded border {{ $documents->currentPage() == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
               {{ $i }}
            </a>
        @endfor
    </div>
    @endif

    <div x-show="writeModal" 
         x-transition.opacity.duration.300ms
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50"
         style="display: none;"></div>

    <div x-show="writeModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-10 sm:scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
         x-transition:leave-end="opacity-0 translate-y-10 sm:scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-0 md:p-4"
         style="display: none;">

        <div class="bg-white w-full h-full md:h-auto md:max-h-[85vh] md:max-w-lg md:rounded-2xl shadow-2xl flex flex-col overflow-hidden relative">
            
            <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center bg-white shrink-0">
                <h3 class="text-lg font-bold text-gray-800">로그 업로드</h3>
                <button @click="writeModal = false" class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 scrollbar-hide">
                <form id="loadForm" action="{{ $currentUrl }}/write" method="POST" enctype="multipart/form-data" class="space-y-5">
                    <input type="hidden" name="doc_type" id="doc_type" value="LOAD">
                    <div x-data="{ preview: null }">
                        <label class="block text-m font-bold text-gray-700 mb-2">이미지</label>
                        <div class="relative w-full h-40 border-2 border-dashed border-gray-300 rounded-xl bg-gray-50 flex flex-col items-center justify-center text-gray-400 overflow-hidden hover:bg-gray-100 hover:border-indigo-300 transition-colors">
                            
                            <img x-show="preview" :src="preview" class="absolute inset-0 w-full h-full object-contain bg-gray-100 z-10">
                            
                            <div x-show="!preview" class="flex flex-col items-center pointer-events-none">
                                <svg class="w-8 h-8 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-sm">Tap to upload</span>
                            </div>

                            <input type="file" name="content" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20"
                                   @change="preview = URL.createObjectURL($event.target.files[0])">
                                   
                            <button type="button" x-show="preview" @click.prevent="preview = null; $el.parentNode.querySelector('input').value = ''" 
                                    class="absolute top-2 right-2 z-30 bg-black/60 text-white rounded-full p-1 hover:bg-black">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-m font-bold text-gray-700 mb-1">
                            리플
                            <span class="text-m text-gray-400 mt-1">* 췩을 허용하시는 경우 비워주세요.</span>
                        </label>
                        <textarea name="reply" class="w-full h-24 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 text-m outline-none focus:bg-white focus:ring-2 focus:ring-indigo-500 resize-none" placeholder="내용을 입력하세요..."></textarea>
                    </div>
                </form>
            </div>

            <div class="p-4 border-t border-gray-100 bg-gray-50 shrink-0">
                <button type="button" onclick="document.getElementById('loadForm').submit();" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl shadow hover:bg-indigo-700 active:scale-[0.98] transition-all">
                    업로드
                </button>
            </div>

        </div>
    </div>

</div>

@push('scripts')
<script>
function copyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            alert("로그 주소가 복사되었습니다.");
        }).catch(err => {
            console.error(err);
            alert("복사에 실패했습니다.");
        });
    } 
    else {
        let textArea = document.createElement("textarea");
        textArea.value = text;
        
        textArea.style.position = "fixed";
        textArea.style.left = "-9999px";
        document.body.appendChild(textArea);
        
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            alert("로그 주소가 복사되었습니다.");
        } catch (err) {
            console.error('Fallback copy failed', err);
            alert("이 브라우저에서는 복사를 지원하지 않습니다.");
        }
        
        document.body.removeChild(textArea);
    }
}
</script>
@endpush

@endsection