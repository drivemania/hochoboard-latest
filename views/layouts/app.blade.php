@php
    $themeUrl = $base_path . '/public/themes/' . $group->theme; //css가져오실때....
    $name = $group->name ?? 'HOCHOBOARD';
@endphp
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:title" content="{{ $group->name }}">
    <meta property="og:description" content="{{ !empty($group->description) ? $group->description : "" }}">
    <meta property="og:image" content="{{ !empty($group->og_image) ? $base_path . '/public' . $group->og_image : "" }}">
    <title>@yield('title', $name)</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    @if(!empty($group->favicon))
    <link rel="icon" href="{{ $base_path }}/public{{ $group->favicon }}">
    @endif
    <style>
        body { font-family: 'Pretendard', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>
    @hook('layout_head')
    @stack('styles')
</head>
<body>
    @if(isset($_SESSION['flash_message']))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show" 
             class="fixed top-5 right-5 z-50 min-w-[300px] bg-white border-l-4 p-4 shadow-lg rounded 
             {{ $_SESSION['flash_type'] == 'error' ? 'border-red-500 text-red-700' : 'border-green-500 text-green-700' }}">
             <p class="font-bold">{{ $_SESSION['flash_type'] == 'error' ? '오류' : '성공' }}</p>
             <button @click="show = false" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
             </button>
             <p>{{ $_SESSION['flash_message'] }}</p>
        </div>
        @php unset($_SESSION['flash_message'], $_SESSION['flash_type']); @endphp
    @endif

    <div id="hc-notification-area" class="fixed top-20 left-1/2 -translate-x-1/2 z-[9999] w-80 pointer-events-none flex flex-col items-center">
    </div>

    @yield('theme_content')
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if(($group->use_notification ?? 1) == 0) return; @endif

            function checkNotifications() {
                fetch('{{ $base_path }}/api/notifications/check')
                    .then(res => {
                        if(res.ok) return res.json();
                        throw new Error('Network response was not ok');
                    })
                    .then(data => {
                        if (data.notifications && data.notifications.length > 0) {
                            data.notifications.forEach(noti => {
                                showBubble(noti);
                            });
                        }
                    })
            }

            function showBubble(noti) {
                const area = document.getElementById('hc-notification-area');
                const bubble = document.createElement('div');
                
                bubble.className = "pointer-events-auto cursor-pointer relative flex items-center w-full bg-white border-2 border-indigo-500 text-gray-800 px-4 py-3 rounded-2xl shadow-xl mb-3 transition-all duration-300 opacity-0 -translate-y-4";
                
                let icon = ''; 
                let iconHtml = '';
                
                if (noti.char_img) {
                    iconHtml = `<div class="w-10 h-10 rounded-full border border-gray-200 overflow-hidden mr-3 shrink-0">
                                    <img src="${noti.char_img}" class="w-full h-full object-cover">
                                </div>`;
                } else {
                    icon = '📩'; 
                    if(noti.type === 'comment') icon = '💬';
                    iconHtml = `<div class="mr-3 text-2xl">${icon}</div>`;
                }

                bubble.innerHTML = `
                    ${iconHtml}
                    <div class="flex-1 text-sm font-bold break-keep leading-tight">${noti.message}</div>
                `;

                if (noti.url) {
                    bubble.onclick = function() {
                        if(noti.type === 'memo') {
                            window.open('{{ $base_path }}/memo', 'memo', 'width=650,height=700');
                        } else {
                            location.href = '{{ $base_path }}' + noti.url;
                        }
                        removeBubble(bubble);
                    };
                }

                area.appendChild(bubble);
                requestAnimationFrame(() => {
                    bubble.classList.remove('opacity-0', '-translate-y-4');
                    bubble.classList.add('opacity-100', 'translate-y-0');
                });

                // setTimeout(() => {
                //     removeBubble(bubble);
                // }, 5000);
            }

            function removeBubble(el) {
                el.classList.remove('opacity-100', 'translate-y-0');
                el.classList.add('opacity-0', '-translate-y-4');
                
                setTimeout(() => {
                    if(el.parentNode) el.parentNode.removeChild(el);
                }, 300);
            }

            @if(isset($_SESSION['user_idx']))
                setInterval(checkNotifications, 5000);
            @endif
        });
    </script>
</body>
</html>
