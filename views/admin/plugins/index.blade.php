@extends('layouts.admin')

@section('title', '플러그인 관리')
@section('header', '확장 기능 관리')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50">
        <h3 class="text-lg font-bold text-gray-800">설치된 플러그인 목록</h3>
        <p class="text-sm text-gray-500 mt-1">/plugins 폴더에 업로드된 플러그인을 활성화하거나 비활성화할 수 있습니다.</p>
    </div>

    @if(empty($plugin))
        <div class="p-12 text-center text-gray-400">
            <svg class="w-12 h-12 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            <p>설치된 플러그인이 없습니다.</p>
            <p class="text-xs mt-2">/plugins 폴더에 플러그인 폴더를 업로드해주세요.</p>
        </div>
    @else
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">플러그인 정보</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">버전 / 경로</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">상태</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($plugin as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10 rounded-lg bg-indigo-100 hidden md:flex items-center justify-center text-indigo-600 font-bold text-lg mr-4">
                                    {{ mb_substr($p['name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-gray-900">{{ $p['name'] }}</div>
                                    <div class="text-sm text-gray-500">{{ $p['description'] }}</div>
                                    <div class="text-xs text-gray-400 mt-1">By {{ $p['author'] }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 mb-1">
                                v{{ $p['version'] }}
                            </span>
                            <div class="text-xs text-gray-400 font-mono">/{{ $p['directory'] }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <form action="{{ $base_path }}/admin/plugins/toggle" method="POST">
                                <input type="hidden" name="directory" value="{{ $p['directory'] }}">
                                <input type="hidden" name="id" value="{{ $p['id'] }}">
                                
                                <label class="relative inline-flex items-center cursor-pointer group">
                                    <input type="checkbox" class="sr-only peer" onchange="this.form.submit()" {{ $p['is_active'] ? 'checked' : '' }}>
                                    
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    
                                    <span class="hidden md:block ml-3 text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors">
                                        {{ $p['is_active'] ? '사용 중' : '사용 안 함' }}
                                    </span>
                                </label>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection