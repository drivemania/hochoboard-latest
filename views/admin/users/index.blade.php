@extends('layouts.admin')
@section('title', '회원 관리')
@section('header', '회원 목록')

@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden" x-data="{ deleteMode: false, allSelected: false }">
    
    <form action="{{ $base_path }}/admin/users/deleteList" method="POST" onsubmit="return confirm('정말 선택한 회원을 탈퇴시키겠습니까?');">
        {{-- @csrf --}}
        
        <div class="p-4 border-b bg-neutral-50 flex justify-between items-center">
            <p class="text-sm text-neutral-600">총 <b>{{ $users->total() }}</b>명의 회원이 있습니다.</p>
            
            <div class="flex gap-2">
                <button type="button" 
                        x-show="!deleteMode" 
                        @click="deleteMode = true"
                        class="bg-red-100 text-red-600 px-3 py-1.5 rounded hover:bg-red-200 text-xs font-bold transition">
                    탈퇴회원 선택
                </button>

                <template x-if="deleteMode">
                    <div class="flex gap-2">
                        <button type="button" 
                                @click="deleteMode = false; allSelected = false"
                                class="bg-white border border-neutral-300 text-neutral-600 px-3 py-1.5 rounded hover:bg-neutral-50 text-xs font-bold transition">
                            취소
                        </button>
                        <button type="submit" 
                                class="bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 text-xs font-bold transition shadow-sm">
                            선택한 회원 탈퇴
                        </button>
                    </div>
                </template>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-white border-b text-neutral-600 text-sm uppercase">
                        <th class="px-6 py-3 w-16">
                            <span x-show="!deleteMode">ID</span>
                            <div x-show="deleteMode" class="flex items-center">
                                <input type="checkbox" 
                                       @change="allSelected = $el.checked; $el.closest('table').querySelectorAll('.user-checkbox').forEach(cb => cb.checked = allSelected)"
                                       class="rounded border-gray-300 text-amber-500 focus:ring-amber-500">
                            </div>
                        </th>
                        
                        <th class="px-6 py-3">아이디</th>
                        <th class="px-6 py-3">닉네임</th>
                        <th class="px-6 py-3">레벨</th>
                        
                        <th class="px-6 py-3 cursor-pointer hover:bg-neutral-50 group">
                            <a href="?sort=created_at&order={{ (isset($sort) && $sort === 'created_at' && isset($order) && $order === 'desc') ? 'asc' : 'desc' }}" class="flex items-center gap-1 text-neutral-600">
                                가입일
                                <span class="text-[10px] text-neutral-400 group-hover:text-amber-500">
                                    @if(isset($sort) && $sort === 'created_at')
                                        {{ (isset($order) && $order === 'asc') ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        
                        <th class="px-6 py-3 cursor-pointer hover:bg-neutral-50 group">
                            <a href="?sort=last_login_at&order={{ (isset($sort) && $sort === 'last_login_at' && isset($order) && $order === 'desc') ? 'asc' : 'desc' }}" class="flex items-center gap-1 text-neutral-600">
                                마지막 접속일
                                <span class="text-[10px] text-neutral-400 group-hover:text-amber-500">
                                    @if(isset($sort) && $sort === 'last_login_at')
                                        {{ (isset($order) && $order === 'asc') ? '▲' : '▼' }}
                                    @else
                                        ↕
                                    @endif
                                </span>
                            </a>
                        </th>
                        
                        <th class="px-6 py-3 text-right">관리</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 text-sm">
                    @foreach($users as $user)
                    <tr class="hover:bg-neutral-50 transition">
                        <td class="px-6 py-4">
                            <span x-show="!deleteMode" class="text-neutral-400">{{ $user->id }}</span>
                            <div x-show="deleteMode">
                                <input type="checkbox" 
                                       name="ids[]" 
                                       value="{{ $user->id }}"
                                       class="user-checkbox rounded border-gray-300 text-amber-500 focus:ring-amber-500">
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 font-bold text-neutral-800">{{ $user->user_id }}</td>
                        <td class="px-6 py-4">
                            {{ $user->nickname }}
                            @if($user->is_deleted)
                                <span class="text-xs text-red-500 font-bold ml-1">(탈퇴)</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($user->level >= 10)
                                <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs font-bold">관리자</span>
                            @else
                                <span class="bg-neutral-100 text-neutral-600 px-2 py-1 rounded text-xs">일반 (Lv.{{ $user->level }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-neutral-500">{{ substr($user->created_at, 0, 10) }}</td>
                        <td class="px-6 py-4 text-neutral-500">{{ substr($user->last_login_at, 0, 10) }}</td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ $base_path }}/admin/users/{{ $user->id }}" class="bg-amber-500 text-white px-3 py-1 rounded hover:bg-amber-700 text-xs font-bold">
                                관리
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
        <div class="p-4 border-t flex justify-center space-x-1">
        @if($users->lastPage() > 1)
            @for($i = 1; $i <= $users->lastPage(); $i++)
                <a href="?page={{ $i }}&sort={{ $sort ?? 'created_at' }}&order={{ $order ?? 'desc' }}" 
                   class="px-3 py-1 rounded border {{ $users->currentPage() == $i ? 'bg-amber-500 text-white border-amber-500' : 'bg-white text-neutral-600 border-neutral-300 hover:bg-neutral-50' }}">
                   {{ $i }}
                </a>
            @endfor
        @endif
    </div>
</div>
@endsection