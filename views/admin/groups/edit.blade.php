@extends('layouts.admin')

@section('title', '그룹 설정 - ' . $group->name)
@section('header', '그룹 상세 설정')

@section('content')

<form action="{{ $base_path }}/admin/groups/update" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="id" value="{{ $group->id }}">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-neutral-800 mb-4 border-b pb-2">🛠 기본 설정</h3>
                
                <div class="mb-4">
                    <label class="block text-neutral-700 text-sm font-bold mb-2">커뮤니티 이름</label>
                    <input type="text" name="name" value="{{ $group->name }}" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-amber-400 outline-none">
                </div>
                
                <div class="mb-4">
                    <label class="block text-neutral-700 text-sm font-bold mb-2">
                        접속 주소 (Slug)
                        <div x-data="{ tooltip: false, modal: false }" class="inline-block ml-1 relative align-middle">
                            <button type="button" @mouseenter="tooltip=true" @mouseleave="tooltip=false" class="text-neutral-400 hover:text-amber-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></button>
                            <div x-show="tooltip" class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-neutral-800 text-white text-xs rounded py-1 px-2 z-10 text-center pointer-events-none">
                                domain.com/au/{slug} 와 같이 대표 커뮤니티가 아닐 경우(AU 사이트일 경우) 접속할 경로를 설정해주세요.
                            </div>
                        </div>
                    </label>
                    <input type="text" value="{{ $group->slug }}" disabled class="w-full border rounded px-3 py-2 bg-neutral-100 text-neutral-500 cursor-not-allowed">
                    <p class="text-xs text-neutral-500 mt-1">접속 주소는 생성 후 변경할 수 없습니다.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-neutral-700 text-sm font-bold mb-2">한 줄 소개 (Description)</label>
                    <textarea name="description" rows="3" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-amber-400 outline-none">{{ $group->description }}</textarea>
                    <p class="text-xs text-neutral-500 mt-1">메타 태그의 description에 들어갈 내용입니다.</p>
                </div>

                <div class="mb-4">
                    <label class="block text-neutral-700 text-sm font-bold mb-2">
                        재화명
                        <div x-data="{ tooltip: false, modal: false }" class="inline-block ml-1 relative align-middle">
                            <button type="button" @mouseenter="tooltip=true" @mouseleave="tooltip=false" class="text-neutral-400 hover:text-amber-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></button>
                            <div x-show="tooltip" class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-neutral-800 text-white text-xs rounded py-1 px-2 z-10 text-center pointer-events-none">
                                커뮤니티에서 쓰일 재화의 이름(예시: 포인트, 원, 달러 등...)을 설정해주세요.
                            </div>
                        </div>
                    </label>
                    <input name="point_name" type="text" value="{{ $group->point_name }}" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-amber-400 outline-none">
                </div>

                <div class="mt-4 p-4 border rounded bg-neutral-50">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="use_notification" value="1" class="w-5 h-5 text-amber-500 rounded" 
                            {{ $group->use_notification ? 'checked' : '' }}>
                        <span class="ml-2 font-bold text-neutral-700">🔔 알람 기능 사용</span>
                    </label>
                    <p class="text-xs text-neutral-500 mt-1 ml-7">체크하면 호출 및 쪽지 도착시 실시간 알람이 옵니다.</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm" x-data="{ isChecked: {{ $group->custom_main_id > 0 ? 'true' : 'false' }} }">
                <h3 class="text-lg font-bold text-neutral-800 mb-4 border-b pb-2">📑 커스텀 메인 페이지 설정</h3>
        
                <div class="mt-4 p-4 border rounded bg-neutral-50">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" x-model="isChecked" name="use_custom_main" value="1" class="w-5 h-5 text-amber-500 rounded" >
                        <span class="ml-2 font-bold text-neutral-700">기능 사용</span>
                    </label>
                    <div>
                    <select name="custom_main_id" x-show="isChecked" class="w-full border border-neutral-300 rounded mt-4 px-3 py-2 focus:ring-2 focus:ring-amber-400 outline-none">
                        @foreach($page as $p)
                            <option value="{{ $p->id }}" {{ $group->custom_main_id === $p->id ? "selected" : "" }}>{{ $p->title }} (ID: {{ $p->id }})</option>
                        @endforeach
                    </select>
                    </div>
                    <p class="text-xs text-neutral-500 mt-1 ml-7">체크하면 스킨 내 index 파일이 아닌 선택한 페이지가 메인 화면이 됩니다.</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-neutral-800 mb-4 border-b pb-2">🎨 디자인/테마</h3>
                
                <div class="mb-6">
                    <label class="block text-neutral-700 text-sm font-bold mb-2">
                        테마 선택
                        <div x-data="{ tooltip: false, modal: false }" class="inline-block ml-1 relative align-middle">
                            <button type="button" @mouseenter="tooltip=true" @mouseleave="tooltip=false" class="text-neutral-400 hover:text-amber-400"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></button>
                            <div x-show="tooltip" class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-neutral-800 text-white text-xs rounded py-1 px-2 z-10 text-center pointer-events-none">
                                홈페이지에 접속했을 때 보이는 전체적인 홈 디자인을 설정합니다.
                            </div>
                        </div>
                    </label>

                    <div x-data="{ selectedTheme: '{{ $group->theme }}' }" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        
                        <input type="hidden" name="theme" :value="selectedTheme">

                        @foreach($themes as $theme)
                        <div @click="selectedTheme = '{{ $theme['id'] }}'" 
                            class="cursor-pointer border-2 rounded-lg overflow-hidden transition relative group"
                            :class="selectedTheme == '{{ $theme['id'] }}' ? 'border-amber-400 ring-2 ring-amber-200' : 'border-neutral-200 hover:border-neutral-300'">
                            
                            <div class="aspect-w-1 aspect-h-1 bg-neutral-100 relative">
                                @if($theme['thumb'])
                                    <img src="{{ $theme['thumb'] }}" class="w-full h-40 object-cover">
                                @else
                                    <div class="w-full h-40 flex items-center justify-center text-neutral-400 bg-neutral-100">
                                        <span>No Image</span>
                                    </div>
                                @endif

                                <div x-show="selectedTheme == '{{ $theme['id'] }}'" class="absolute inset-0 bg-amber-400 bg-opacity-20 flex items-center justify-center">
                                    <div class="bg-amber-500 text-white rounded-full p-2">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3">
                                <h4 class="font-bold text-neutral-800 text-sm">{{ $theme['name'] }}</h4>
                                <p class="text-xs text-neutral-500 mt-1 line-clamp-2">{{ $theme['description'] }}</p>
                            </div>
                        </div>
                        @endforeach

                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-neutral-700 text-sm font-bold mb-2">파비콘 (Favicon)</label>
                        <input type="file" name="favicon" accept=".ico,.png" class="text-sm">
                        @if($group->favicon)
                            <div class="mt-2 p-2 border rounded bg-neutral-50 inline-block">
                                <img src="{{ $base_path . $group->favicon }}" class="w-8 h-8">
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-neutral-700 text-sm font-bold mb-2">SNS 공유 이미지 (OG Image)</label>
                        <input type="file" name="og_image" accept="image/*" class="text-sm">
                        @if($group->og_image)
                            <div class="mt-2 border rounded bg-neutral-50 inline-block">
                                <img src="{{ $base_path . $group->og_image }}" class="h-20 object-cover">
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white p-6 rounded-lg shadow-sm sticky top-6">
                <h3 class="text-lg font-bold text-neutral-800 mb-4 border-b pb-2">💾 저장</h3>
                
                <div class="mb-6">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" {{ $group->is_default ? 'checked' : '' }} class="mt-1 form-checkbox h-5 w-5 text-amber-500">
                        <div class="ml-2">
                            <span class="block text-sm font-bold text-neutral-800">대표 커뮤니티로 설정</span>
                            <span class="block text-xs text-neutral-500">체크 시 루트 도메인 접속이 이 그룹으로 연결됩니다.</span>
                        </div>
                    </label>
                </div>

                <button type="submit" class="w-full bg-amber-500 hover:bg-amber-700 text-white font-bold py-3 px-4 rounded transition shadow-lg">
                    설정 저장하기
                </button>
                
                <a href="{{ $base_path }}/admin/groups" class="block text-center mt-4 text-neutral-500 hover:text-neutral-700 text-sm">
                    목록으로 돌아가기
                </a>
            </div>
        </div>

    </div>
</form>

@endsection