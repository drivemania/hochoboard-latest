@extends('layouts.admin')
@section('title', '회원 정보 수정')
@section('header', '회원 정보 수정')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ $base_path }}/admin/users/update" method="POST">
        <input type="hidden" name="id" value="{{ $user->id }}">

        <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
            <h3 class="text-lg font-bold border-b pb-2 mb-4">기본 정보</h3>
            
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2 text-neutral-700">아이디</label>
                    <input type="text" value="{{ $user->user_id }}" class="w-full border rounded px-3 py-2 bg-neutral-100 text-neutral-500" readonly>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-2 text-neutral-700">닉네임</label>
                    <input type="text" name="nickname" value="{{ $user->nickname }}" class="w-full border rounded px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-2 text-neutral-700">이메일</label>
                    <input type="email" name="email" value="{{ $user->email }}" class="w-full border rounded px-3 py-2">
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm mb-6">
            <h3 class="text-lg font-bold border-b pb-2 mb-4">권한 및 보안</h3>
            
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-bold mb-2 text-neutral-700">회원 등급 (Level)</label>
                    <select name="level" class="w-full border rounded px-3 py-2 bg-white">
                        <option value="1" {{ $user->level == 1 ? 'selected' : '' }}>1</option>
                        <option value="2" {{ $user->level == 2 ? 'selected' : '' }}>2</option>
                        <option value="3" {{ $user->level == 3 ? 'selected' : '' }}>3</option>
                        <option value="4" {{ $user->level == 4 ? 'selected' : '' }}>4</option>
                        <option value="5" {{ $user->level == 5 ? 'selected' : '' }}>5</option>
                        <option value="6" {{ $user->level == 6 ? 'selected' : '' }}>6</option>
                        <option value="7" {{ $user->level == 7 ? 'selected' : '' }}>7</option>
                        <option value="8" {{ $user->level == 8 ? 'selected' : '' }}>8</option>
                        <option value="9" {{ $user->level == 9 ? 'selected' : '' }}>9</option>
                        <option value="10" {{ $user->level == 10 ? 'selected' : '' }}>10 (관리자)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold mb-2 text-neutral-700">비밀번호 변경</label>
                    <input type="password" name="password" class="w-full border rounded px-3 py-2" placeholder="변경할 경우에만 입력하세요">
                    <p class="text-xs text-neutral-500 mt-1">※ 입력하지 않으면 기존 비밀번호가 유지됩니다.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <button type="button" onclick="deleteUser()" class="text-red-500 font-bold text-sm hover:underline">
                이 회원 강제 탈퇴(삭제)
            </button>

            <div class="space-x-2">
                <a href="{{ $base_path }}/admin/users" class="px-4 py-2 border rounded text-neutral-600 hover:bg-neutral-50">취소</a>
                <button type="submit" class="bg-amber-500 text-white px-6 py-2 rounded font-bold hover:bg-amber-700">저장하기</button>
            </div>
        </div>
    </form>

    <form id="deleteForm" action="{{ $base_path }}/admin/users/delete" method="POST">
        <input type="hidden" name="id" value="{{ $user->id }}">
    </form>
</div>
@push('scripts')
<script>
    function deleteUser() {
        if(confirm('정말 이 회원을 삭제하시겠습니까?\n작성한 글과 댓글은 유지될 수 있습니다.')) {
            document.getElementById('deleteForm').submit();
        }
    }
</script>
@endpush
@endsection