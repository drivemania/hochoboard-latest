@extends('layouts.admin')

@section('title', '호쵸보드 설정')
@section('header', '대시보드')

@section('content')
<div class="p-6">
    <div class="grid grid-cols-1 gap-6 mb-6">
        @if ($updateInfo['has_update'])
        <div class="bg-indigo-600 text-white p-4 rounded-lg shadow-lg mb-6 flex justify-between items-center">
            <div>
                <span class="bg-white text-indigo-600 text-xs font-bold px-2 py-1 rounded uppercase mr-2">New Update</span>
                <span class="font-medium">
                    새로운 버전({{ $updateInfo['latest_version'] }})이 출시되었습니다!
                </span>
                <p class="text-indigo-200 text-sm mt-1">
                    {{ $updateInfo['message'] }} (현재 버전: {{ $updateInfo['current_version'] }})
                </p>
            </div>
            <a href="{{ $updateInfo['link'] }}" target="_blank" class="bg-white text-indigo-600 px-4 py-2 rounded-lg font-bold hover:bg-indigo-50 transition">
                업데이트 확인
            </a>
        </div>
        @endif

        <div class="bg-white rounded-lg shadow p-5">
            <form action="{{ $base_path }}/admin/issecret" method="POST" onsubmit="return confirm('공개 설정을 변경하시겠습니까?');">
                <h3 class="flex text-lg font-bold text-gray-800 mb-4 border-b pb-2 justify-between">
                    🛠 공개 설정
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm font-bold">변경하기</button>
                </h3>
                <select name="is_secret" class="w-full border border-gray-300 rounded px-3 py-2 focus:ring-2 focus:ring-indigo-500 outline-none">
                    <option value="0" {{ $group->is_secret === 0 ? "selected" : "" }}>전체 공개</option>
                    <option value="1" {{ $group->is_secret === 1 ? "selected" : "" }}>회원가입 불가</option>
                    <option value="2" {{ $group->is_secret === 2 ? "selected" : "" }}>비공개</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">사이트의 공개 여부를 설정합니다.</p>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">최근 활동</h3>
            </div>
            <ul class="flex flex-col space-y-1 mb-4">
            @if ($board->isEmpty()) 
                <li class="py-8 text-center text-gray-400 text-sm">등록된 새 글이 없습니다.</li>
            @else
                @foreach ($board as $item) 
                    @php
                    $cutSubject = 20;
                    $subject = strip_tags($item->subject);
                    if (mb_strlen($subject) > $cutSubject) {
                        $subject = mb_substr($subject, 0, $cutSubject) . '...';
                    }
                    
                    if(mb_strlen($subject) <= 0){
                        $subject = '...';
                    }

                    $url = $base_path . '/' . $item->menu_slug . '/' . $item->doc_id;
                    if ($item->type === 'cmt') {
                        $url .= '#comment_' . $item->comment_id;
                    }

                    $date = date('m-d', strtotime($item->created_at));
                    if (date('Y-m-d') == date('Y-m-d', strtotime($item->created_at))) {
                        $date = date('H:i', strtotime($item->created_at));
                    }
                    if ($item->type === 'doc') {
                        $badgeClass = 'bg-indigo-100 text-indigo-600 border border-indigo-200';
                        $badgeText = '글';
                    } else {
                        $badgeClass = 'bg-green-100 text-green-600 border border-green-200';
                        $badgeText = '댓글';
                    }
                    @endphp

                    <li class="group flex items-center justify-between py-2 px-2 -mx-2 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                    <div class="flex items-center min-w-0 gap-2 pr-4">
                    <span class="flex-shrink-0 px-1.5 py-0.5 rounded text-[11px] font-bold {{ $badgeClass }}">{{ $badgeText }}</span>
                    <a href="{{ $url }}" class="text-sm text-gray-700 group-hover:text-indigo-600 transition-colors truncate block">
                        {{ $subject }}
                    </a>
                    @if (strtotime($item->created_at) > time() - 86400)
                        <span class="flex-shrink-0 w-4 h-4 flex items-center justify-center rounded-full bg-red-500 text-white text-[10px] font-bold shadow-sm" title="New">N</span>
                    @endif
                    </div>
                    <span class="flex-shrink-0 text-xs text-gray-400 font-medium whitespace-nowrap">{{ $date }}</span>
                    </li>
                @endforeach
            @endif
            </ul>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-5">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex justify-between items-end">
                <div class="flex items-baseline gap-2">
                    <span>회원 목록</span>
                    <span class="text-sm text-gray-500 font-normal">
                        총 회원수: <span class="font-bold text-green-600">{{ count($user) }}</span>
                    </span>
                </div>

                <a href="{{ $base_path }}/admin/users" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-xs text-gray-700 transition">
                    전체 회원 목록
                </a>
            </h3>
            <div class="mt-4 items-center text-sm text-gray-500">
                <div class="overflow-x-auto">
                @if ($user->isEmpty()) 
                    <span class="py-8 text-center text-gray-400 text-sm">가입 회원이 없습니다.</span>
                @else
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th scope="col" class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    ID
                                </th>
                                <th scope="col" class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    닉네임
                                </th>
                                <th scope="col" class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    권한
                                </th>
                                <th scope="col" class="px-6 py-2 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    가입일자
                                </th>
                            </tr>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($user as $u)
                                <tr class="hover:bg-indigo-50/30 transition-colors duration-200">
                                <td class="px-6 py-2 whitespace-nowrap">
                                    {{ $u->user_id }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-700">
                                    {{ $u->nickname }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap">
                                    {{ $u->level }}
                                </td>
                                <td class="px-6 py-2 whitespace-nowrap text-sm text-gray-500">
                                    {{ date("Y-m-d", strtotime($u->created_at))  }}
                                </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </thead>
                    </table>
                @endif
                    
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-5">
             <h3 class="text-lg font-semibold text-gray-800 mb-4">시스템 정보 & 퀵 액션</h3>
             <div class="text-sm text-gray-600 mb-4 space-y-1">
                <p><span class="font-semibold w-20 inline-block">PHP:</span> v{{ phpversion() }}</p>
                <p><span class="font-semibold w-20 inline-block">Server:</span> {{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}</p>
                <p><span class="font-semibold w-20 inline-block">버전:</span> {{ $updateInfo['current_version'] }}</p>
             </div>
             <h4 class="font-semibold text-gray-800 mb-2">캐시 관리</h4>
             <div class="flex space-x-2">
                <form action="{{ $base_path }}/admin/system/clear-cache" method="POST" onsubmit="return confirm('뷰 캐시를 삭제하시겠습니까?');" class="flex-1">
                    <button type="submit" class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-xs text-gray-700 transition">
                        View 캐시 삭제
                    </button>
                </form>
                
                <form action="{{ $base_path }}/admin/system/clear-session" method="POST" onsubmit="return confirm('경고: 로그인된 모든 사용자가 로그아웃됩니다. 진행할까요?');" class="flex-1">
                    <button type="submit" class="w-full px-3 py-2 bg-gray-100 hover:bg-gray-200 rounded text-xs text-gray-700 transition">
                        세션 비우기
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection