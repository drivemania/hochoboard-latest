@extends('layouts.admin')

@section('title', '그룹 관리')
@section('header', '커뮤니티 그룹 설정')

@section('content')

{{-- Alpine.js 데이터 선언 (모달 상태 관리) --}}
<div x-data="{ showModal: false }">

    <div class="flex justify-end mb-4">
        <button @click="showModal = true" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-bold flex items-center">
            <span class="mr-2">➕</span> 새 그룹 생성
        </button>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full text-left text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b border-gray-200 text-gray-600 text-sm uppercase">
                    <th class="hidden md:table-cell px-6 py-3">ID</th>
                    <th class="px-6 py-3">그룹명 (접속주소)</th>
                    <th class="px-6 py-3">스킨</th>
                    <th class="px-6 py-3">상태</th>
                    <th class="px-6 py-3 text-right">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($group as $g)
                <tr class="hover:bg-gray-50">
                    <td class="hidden md:table-cell px-6 py-4 text-gray-500">#{{ $g->id }}</td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-800">{{ $g->name }}</div>
                        <div class="text-sm text-gray-500">/au/{{ $g->slug }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="bg-gray-100 text-gray-600 py-1 px-2 rounded text-xs">{{ $g->theme }}</span>
                    </td>
                    <td class="px-6 py-4">
                        @if($g->is_default)
                            <span class="bg-green-100 text-green-700 py-1 px-2 rounded-full text-xs font-bold">대표 그룹</span>
                        @else
                            <span class="text-gray-400 text-xs">일반</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        <a href="{{ $base_path }}/admin/groups/{{ $g->id }}" class="inline-block text-blue-600 hover:text-blue-800 text-sm font-semibold bg-blue-50 hover:bg-blue-100 py-1 px-3 rounded">
                            ⚙️ 설정
                        </a>

                        <form action="{{ $base_path }}/admin/groups/delete" method="POST" class="inline-block" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                            <input type="hidden" name="id" value="{{ $g->id }}">
                            <button type="submit" class="text-red-500 hover:text-red-700 text-sm font-semibold py-1 px-2">삭제</button>
                        </form>
                    </td>
                </tr>
                @endforeach

                @if(count($group) === 0)
                <tr>
                    <td colspan="5" class="px-6 py-10 text-center text-gray-500">
                        생성된 그룹이 없습니다.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            
            <div x-show="showModal" x-transition.opacity class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showModal = false"></div>

            <div x-show="showModal" x-transition.scale class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="{{ $base_path }}/admin/groups" method="POST">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">새 커뮤니티 그룹 생성</h3>
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">커뮤니티 이름</label>
                            <input type="text" name="name" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="예: OO아파트, 마피아AU" required>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 text-sm font-bold mb-2">
                                접속 주소 (Slug)
                                <div x-data="{ tooltip: false, modal: false }" class="inline-block ml-1 relative align-middle">
                                    <button type="button" @mouseenter="tooltip=true" @mouseleave="tooltip=false" class="text-gray-400 hover:text-blue-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg></button>
                                    <div x-show="tooltip" class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 w-48 bg-gray-800 text-white text-xs rounded py-1 px-2 z-10 text-center pointer-events-none">
                                        domain.com/au/{slug} 와 같이 대표 커뮤니티가 아닐 경우(AU 사이트일 경우) 접속할 경로를 설정해주세요.
                                    </div>
                                </div>

                            </label>
                            <input type="text" name="slug" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="예: mafiaau (영문)" required>
                            <p class="text-xs text-gray-500 mt-1">접속 주소: domain.com/au/<b>mafiaau</b></p>
                        </div>

                        <div class="mb-4">
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="is_default" value="1" class="form-checkbox h-5 w-5 text-blue-600">
                                <span class="ml-2 text-gray-700">이 그룹을 <b>대표 커뮤니티</b>로 설정</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1 pl-7">체크 시 루트 도메인 접속 화면이 이 그룹으로 변경됩니다.</p>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">
                            생성하기
                        </button>
                        <button type="button" @click="showModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            취소
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection