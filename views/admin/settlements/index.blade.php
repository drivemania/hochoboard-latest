@extends('layouts.admin')
@section('title', '정산 관리')
@section('header', '정산 관리')

@section('content')
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="p-4 border-b bg-neutral-50">
        <p class="text-sm text-neutral-600">정산을 관리할 커뮤니티 그룹을 선택하세요.</p>
    </div>
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-white border-b text-neutral-600 text-sm uppercase">
                <th class="px-6 py-3">그룹명</th>
                <th class="px-6 py-3">URL ID</th>
                <th class="px-6 py-3 text-right">관리</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-neutral-200 text-sm">
            @foreach($groups as $g)
            <tr class="hover:bg-neutral-50 transition">
                <td class="px-6 py-4 font-bold text-neutral-800">{{ $g->name }}</td>
                <td class="px-6 py-4 text-amber-500 text-sm">/au/{{ $g->slug }}</td>
                <td class="px-6 py-4 text-right">
                    <a href="{{ $base_path }}/admin/settlements/{{ $g->id }}" class="bg-amber-500 text-white px-4 py-2 rounded hover:bg-amber-700 text-sm font-bold">
                        설정
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection