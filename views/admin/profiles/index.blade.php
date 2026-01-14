@extends('layouts.admin')
@section('title', '프로필 양식 설정')
@section('header', '캐릭터 양식 관리')

@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b bg-gray-50">
        <p class="text-sm text-gray-600">프로필 양식을 설정할 <b>커뮤니티 그룹</b>을 선택하세요.</p>
    </div>

    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-white border-b text-gray-600 text-sm uppercase">
                <th class="px-6 py-3">그룹명</th>
                <th class="px-6 py-3">URL ID</th>
                <th class="px-6 py-3">현재 설정</th>
                <th class="px-6 py-3 text-right">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
            @foreach($group as $group)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-bold text-gray-800">{{ $group->name }}</td>
                <td class="px-6 py-4 text-blue-600">/au/{{ $group->slug }}</td>
                <td class="px-6 py-4">
                    @if($group->use_fixed_char_fields)
                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-bold">✅ 관리자 양식 사용중</span>
                    @else
                        <span class="bg-gray-100 text-gray-500 px-2 py-1 rounded-full text-xs">자유 입력 모드</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ $base_path }}/admin/profiles/{{ $group->id }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm font-bold">
                        설정
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    </div>
@endsection