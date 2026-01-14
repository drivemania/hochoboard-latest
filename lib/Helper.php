<?php
use Illuminate\Database\Capsule\Manager as DB;

class Helper {
    /**
     * 자동 링크 치환
     * @param string $text 내용
     */
    public static function auto_link($text) {
        $pattern = '/(?<!src=["\'])(?<!href=["\'])(http|https|ftp):\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
        $replacement = '<a href="$0" target="_blank" class="hc-auto-link">$0</a>';
        return preg_replace($pattern, $replacement, $text);
    }

    /**
     * 자동 해시태그 치환
     * @param string $text 내용
     * @param string $currentUrl 링크 설정할 url(게시판에서 사용할 예정이라면 무조건 list url을 줘야함)
     */
    public static function auto_hashtag($text, $currentUrl) {
        $pattern = '/(?<!\w)#([a-zA-Z0-9_가-힣]+)/u';
        $replacement = '<a href="'.$currentUrl.'?search_target=hashtag&keyword=$1" target="_blank" class="hc-hashtag">#$1</a>';
        return preg_replace($pattern, $replacement, $text);
    }

    /**
     * 대표 캐릭터 가져오는 함수
     * @param string  $mid     멤버 고유 코드
     * @param string  $gid     그룹 고유 코드
     * @return object $results 캐릭터ID, 캐릭터 두상, 이름, Slug 정보만 return
     */
    public static function getMyMainChr($mid, $gid) {
        $results = DB::table('characters')
            ->join('menus', 'characters.board_id', '=', 'menus.target_id')
            
            ->where('characters.is_deleted', 0)
            ->where('characters.is_main', 1)
            ->where('characters.group_id', $gid)
            ->where('characters.user_id', $mid)
            ->where('menus.group_id', $gid)
            ->where('menus.is_deleted', 0)
            ->select([
                'characters.id', 
                'characters.image_path', 
                'characters.name',
                'menus.slug as menu_slug'
            ])
            ->first();
        return $results;
    }

    /**
     * 파일 수정 시간을 기반으로 version 붙여주는 함수
     * @param string  $path     파일 위치
     * @return string $path     파일 위치+버전
     */
    public static function asset($path) {
        $realPath = __DIR__ . '/../../' . $path;
        
        if (file_exists($realPath)) {
            $ver = filemtime($realPath);
            return $path . '?v=' . $ver;
        }
        
        return $path;
    }
}