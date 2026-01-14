@extends('layouts.admin')
@section('title', '메뉴 관리')
@section('header', '메뉴 관리')

@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b bg-gray-50">
        <p class="text-sm text-gray-600">메뉴를 설정할 커뮤니티 그룹을 선택하세요.</p>
    </div>
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-white border-b text-gray-600 text-sm uppercase">
                <th class="px-6 py-3">그룹명</th>
                <th class="px-6 py-3">URL ID</th>
                <th class="px-6 py-3">현재 메뉴 수</th>
                <th class="px-6 py-3 text-right">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 text-sm">
            @foreach($group as $g)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 font-bold text-gray-800">{{ $g->name }}</td>
                <td class="px-6 py-4 text-blue-600 text-sm">/au/{{ $g->slug }}</td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    {{ \Illuminate\Database\Capsule\Manager::table('menus')->where('group_id', $g->id)->where('is_deleted', 0)->count() }}개
                </td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ $base_path }}/admin/menus/{{ $g->id }}" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm font-bold">
                        설정
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection