@extends('basic.layout')

@section('title', $title)

@section('content')

    <div x-data="imageSlider()" x-init="startAutoPlay()" class="relative w-full max-w-5xl mx-auto overflow-hidden rounded-lg shadow-lg group mb-6">

        <div class="relative w-full aspect-[10/3] bg-gray-900">
            <div x-show="active === 0" 
                x-transition:enter="transition transform duration-500"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="absolute inset-0 w-full h-full">
                <a href="#"><img src="{{ $themeUrl }}/image/banner1.png?v={{ date("YmdHis") }}" class="w-full h-full object-cover" alt="Slide 1"></a>
            </div>
        </div>

        <button @click="prev()" class="absolute left-0 top-1/2 -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white p-2 m-2 rounded-full opacity-0 group-hover:opacity-100 transition">
            ❮
        </button>

        <button @click="next()" class="absolute right-0 top-1/2 -translate-y-1/2 bg-black bg-opacity-30 hover:bg-opacity-50 text-white p-2 m-2 rounded-full opacity-0 group-hover:opacity-100 transition">
            ❯
        </button>

        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2">
            <template x-for="i in total">
                <button @click="active = i - 1; stopAutoPlay(); startAutoPlay();" 
                        :class="active === i - 1 ? 'bg-white w-6' : 'bg-gray-400 w-2'"
                        class="h-2 rounded-full transition-all duration-300"></button>
            </template>
        </div>

    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @hc_latestPost(5, 20, $group->slug)

        @hc_login($group->slug)
    </div>

@push('scripts')
<script>
    function imageSlider() {
        return {
            active: 0, // 몇번째 슬라이드를 먼저 보여주고 싶은지 지정하시면 됩니다
            total: 1, // 전체 슬라이드 개수
            interval: null,
    
            startAutoPlay() {
                this.interval = setInterval(() => {
                    this.next();
                }, 3000); // 1000 = 1초
            },
    
            stopAutoPlay() { clearInterval(this.interval); },
    
            next() { this.active = (this.active + 1) % this.total; },
    
            prev() { this.active = (this.active - 1 + this.total) % this.total; }
        }
    }
</script>
@endpush
@endsection