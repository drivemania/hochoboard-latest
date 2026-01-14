@extends($themeLayout)

@section('title', '로그인')

@section('content')
<div class="flex flex-col items-center justify-center py-12">
    <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md border border-gray-200">
        <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">로그인</h2>
        
        <form action="{{ $base_path }}/login" method="POST">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">아이디</label>
                <input type="text" name="user_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required autofocus>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">비밀번호</label>
                <input type="password" name="password" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
            </div>
            <div class="mb-6">
                <input type="checkbox" id="auto_login" name="auto_login"/>
                <label for="auto_login" class="text-gray-500 text-sm font-bold mb-2">
                    자동로그인 사용
                </label>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition">
                로그인하기
            </button>
        </form>

        <div class="mt-6 text-center text-sm">
            <a href="{{ $base_path }}/register" class="text-blue-600 hover:underline">아직 회원이 아니신가요?</a>
        </div>
    </div>
</div>
@endsection