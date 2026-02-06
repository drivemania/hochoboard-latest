@extends($themeLayout)

@section('content')

<div class="max-w-6xl mx-auto p-4">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-2xl font-bold text-neutral-800">캐릭터 명단</h2>
        @if(1==1)
        <a href="{{ $currentUrl }}/write" class="bg-amber-600 text-white px-4 py-2 rounded font-bold hover:bg-amber-700">
            + 새 캐릭터 생성
        </a>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($characters as $char)
        <a href="{{ $currentUrl }}/{{ $char->id }}">
            <div class="bg-white rounded-lg shadow border hover:shadow-md transition overflow-hidden relative" >

                <div class="flex p-4">
                    <div class="w-20 h-20 bg-neutral-200 rounded-full flex-shrink-0 overflow-hidden mr-4 border-2 border-neutral-100">
                        <img src="{{ $char->image_path }}" class="w-full h-full object-cover">
                    </div>
                    
                    <div class="flex-1 overflow-hidden">
                        <h3 class="font-bold text-lg text-neutral-800 truncate">{{ $char->name }}</h3>
                        <p class="text-sm text-neutral-500 line-clamp-2 mb-2">{{ $char->nickname }}</p>

                    </div>
                </div>
            </div>
        </a>
        @endforeach

        @if($characters->isEmpty())
        <div class="col-span-full text-center py-10 text-neutral-400">
            생성된 캐릭터가 없습니다.
        </div>
        @endif
    </div>
</div>
@endsection