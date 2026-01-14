@extends('layouts.admin')
@section('title', '회원 관리')
@section('header', '회원 목록')

@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
        <p class="text-sm text-gray-600">총 <b>{{ $users->total() }}</b>명의 회원이 있습니다.</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-white border-b text-gray-600 text-sm uppercase">
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">아이디</th>
                    <th class="px-6 py-3">닉네임</th>
                    <th class="px-6 py-3">레벨</th>
                    <th class="px-6 py-3">가입일</th>
                    <th class="px-6 py-3 text-right">관리</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-sm">
                @foreach($users as $user)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-4 text-gray-400">{{ $user->id }}</td>
                    <td class="px-6 py-4 font-bold text-gray-800">{{ $user->user_id }}</td>
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
                            <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">일반 (Lv.{{ $user->level }})</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-500">{{ substr($user->created_at, 0, 10) }}</td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ $base_path }}/admin/users/{{ $user->id }}" class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 text-xs font-bold">
                            관리
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="p-4 border-t flex justify-center space-x-1">
        @if($users->lastPage() > 1)
            @for($i = 1; $i <= $users->lastPage(); $i++)
                <a href="?page={{ $i }}" 
                   class="px-3 py-1 rounded border {{ $users->currentPage() == $i ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50' }}">
                   {{ $i }}
                </a>
            @endfor
        @endif
    </div>
</div>
@endsection