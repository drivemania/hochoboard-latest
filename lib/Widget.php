<?php
use Illuminate\Database\Capsule\Manager as DB;

class Widget {
    /**
     * 메뉴 출력 위젯
     * @param string $basePath 기본 경로
     * @param string $groupSlug 그룹 slug
     */
    public static function menu($basePath, $groupSlug) {
        if (!$groupSlug) return '';

        $menus = DB::table('menus')
            ->join('groups', 'menus.group_id', '=', 'groups.id')
            ->where('groups.slug', $groupSlug)
            ->where('menus.is_deleted', 0)
            ->orderBy('menus.order_num', 'asc')
            ->select('menus.*')
            ->get();

        if ($menus->isEmpty()) return '';

        $html = '<nav class="hc-menu-widget">';
        $html .= '<ul class="hc-menu-list">';

        foreach ($menus as $m) {
            $group = DB::table('groups')->find($m->group_id);

            $a_target = "";
            if($m->type == 'link'){
                $link = $m->target_url;
                $a_target = 'target="_blank"';
            }else{
                $link = "{$basePath}/au/{$group->slug}/{$m->slug}"; 
                if($group->is_default === 1){
                    $link = "{$basePath}/{$m->slug}"; 
                }
            }

            $currentUri = $_SERVER['REQUEST_URI'] ?? '';
            $isActive = (strpos($currentUri, $m->slug) !== false && $m->type != 'link') ? ' active' : '';

            $html .= '<li class="hc-menu-item' . $isActive . '">';
            $html .= '<a href="' . $link . '" class="hc-menu-link" '.$a_target.'>' . htmlspecialchars($m->title) . '</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';
        $html .= '</nav>';

        return $html;
    }
    /**
     * 로그인 위젯
     * @param string $basePath 기본 경로
     * @param string $groupSlug 그룹 slug
     */
    public static function login($basePath, $groupSlug = null) {
        $html = '<div class="hc-login-widget">';

        if (isset($_SESSION['user_idx'])) {
            $userIdx = $_SESSION['user_idx'] ?? 0;
            $nickname = htmlspecialchars($_SESSION['nickname']);

            $mainChar = null;
            if ($groupSlug && $userIdx) {
                $mainChar = DB::table('characters')
                    ->join('groups', 'characters.group_id', '=', 'groups.id') 
                    ->where('groups.slug', $groupSlug) 
                    ->where('characters.user_id', $userIdx)
                    ->where('characters.is_main', 1)
                    ->select('characters.*') 
                    ->first();
            }

            $html .= '<div x-data="{ count: 0 }" x-init="fetch(\'' . $basePath . '/api/memo/count\').then(r => r.json()).then(d => count = d.count)" class="hc-memo-alert">';
            $html .= '<a href="#" onclick="window.open(\'' . $basePath . '/memo\', \'memo\', \'width=650,height=700\'); return false;" class="hc-memo-link">';
            $html .= '📩 쪽지함 ';
            $html .= '<span x-show="count > 0" class="hc-memo-badge" x-text="count" style="display:none;"></span>';
            $html .= '</a>';
            $html .= '</div>';

            if ($mainChar) {
                $html .= '<div class="hc-main-char">';
                
                $imgSrc = $mainChar->image_path ? $mainChar->image_path : '';
                $html .= '<div class="hc-main-char-img">';
                $html .= '<img src="' . $imgSrc . '" alt="Main Character">';
                $html .= '</div>';
                
                $html .= '<div class="hc-main-char-text">';
                $html .= '<span class="hc-main-char-name">' . htmlspecialchars($mainChar->name) . '</span>';
                $html .= '</div>';
                
                $html .= '</div>';
            } else {
                $html .= '<div class="hc-no-char">';
                $html .= '<div class="hc-no-char-icon">😊</div>';
                $html .= '</div>';
            }
            
            $html .= '<div class="hc-login-info">';
            $html .= '오너 : <span class="hc-login-nickname">' . $nickname . '</span>';
            $html .= '</div>';
            
            $html .= '<div class="hc-login-actions">';
            if (($_SESSION['level'] ?? 0) >= 10) {
                $html .= '<a href="' . $basePath . '/admin" class="hc-login-btn-admin" target="_blank">관리자</a>';
            }
            $html .= '<a href="' . $basePath . '/logout" class="hc-login-btn-logout">로그아웃</a>';
            $html .= '</div>';

        } else {
            $html .= '<form action="' . $basePath . '/login" method="POST" class="hc-login-form">';
            $html .= '<div class="hc-login-inputs">';
            $html .= '<input type="text" name="user_id" placeholder="아이디" class="hc-login-input-id">';
            $html .= '<input type="password" name="password" placeholder="비밀번호" class="hc-login-input-pw">';
            $html .= '</div>';
            $html .= '<div class="hc-login-auto-login">';
            $html .= '<input type="checkbox" id="auto_login" name="auto_login">';
            $html .= '<label for="auto_login">자동 로그인</label>';
            $html .= '</div>';
            
            $html .= '<div class="hc-login-btn-wrap">';
            $html .= '<button type="submit" class="hc-login-btn-submit">로그인</button>';
            $html .= '</div>';
            
            $html .= '<div class="hc-login-links">';
            $html .= '<a href="' . $basePath . '/register" class="hc-login-link-register">회원가입</a>';
            $html .= '</div>';
            $html .= '</form>';
        }

        $html .= '</div>';
        return $html;
    }
    /**
     * 최신글+댓글 통합 위젯
     * @param string $basePath 기본 경로
     * @param int $limit 가져올 개수 (기본 10개)
     * @param int $cutSubject 제목 글자수 제한 (기본 20자)
     * @param string $groupSlug 그룹 slug (생략할경우 전체 게시글 불러옴)
     */
    public static function latestPosts($basePath, $limit = 10, $cutSubject = 20, $groupSlug = null) {

        $docs = DB::table('documents')
            ->join('menus', function($join) {
                $join->on('documents.board_id', '=', 'menus.target_id')
                    ->on('documents.group_id', '=', 'menus.group_id')
                    ->whereIn('menus.type', array('board', 'load'));
            })
            ->where('documents.is_deleted', 0)
            ->where('menus.is_deleted', 0)
            ->where('documents.is_secret', 0)
            ->select(
                'documents.title as subject',
                'documents.created_at',
                'menus.slug as menu_slug',
                'documents.id as doc_id',
                'documents.doc_num as doc_num',
                'menus.type as menu_type',
                DB::raw("NULL as comment_id"),
                DB::raw("'doc' as type")
                    );

        if (!empty($groupSlug)) {
            $docs->join('groups', 'menus.group_id', '=', 'groups.id')
                ->where('groups.slug', $groupSlug);
        }

        $comments = DB::table('comments')
            ->join('documents', 'comments.doc_id', '=', 'documents.id')
            ->join('menus', function($join) {
                $join->on('documents.board_id', '=', 'menus.target_id')
                    ->whereIn('menus.type', array('board', 'load'));
            })
            ->where('comments.is_deleted', 0)
            ->where('menus.is_deleted', 0)
            ->where('documents.is_secret', 0)
            ->where('documents.is_deleted', 0)
            ->select(
                'comments.content as subject',
                'comments.created_at',
                'menus.slug as menu_slug',
                'documents.id as doc_id',
                'documents.doc_num as doc_num',
                'menus.type as menu_type',
                'comments.id as comment_id',
                DB::raw("'cmt' as type")
            );

        if (!empty($groupSlug)) {
            $comments->join('groups', 'menus.group_id', '=', 'groups.id')
                    ->where('groups.slug', $groupSlug);
        }

        $items = $docs->unionAll($comments)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $html = '<div class="hc-latest-widget">';
        $html .= '<h3 class="hc-latest-title">최신 글 & 댓글</h3>';
        $html .= '<ul class="hc-latest-list">';

        if ($items->isEmpty()) {
            $html .= '<li class="hc-latest-empty">등록된 새 글이 없습니다.</li>';
        } else {
            foreach ($items as $item) {
                $subject = strip_tags($item->subject);
                if (mb_strlen($subject) > $cutSubject) {
                    $subject = mb_substr($subject, 0, $cutSubject) . '...';
                }

                if(mb_strlen($subject) <= 0){
                    $subject = '...';
                }

                $url = $basePath . '/' . $item->menu_slug . '/' . $item->doc_num;
                if ($item->type === 'cmt') {
                    $url .= '#comment_' . $item->comment_id;
                }

                $date = date('m-d', strtotime($item->created_at));
                
                if (date('Y-m-d') == date('Y-m-d', strtotime($item->created_at))) {
                    $date = date('H:i', strtotime($item->created_at));
                }

                $typeLabel = ($item->type === 'doc') ? '<span class="hc-latest-badge-doc">글</span>' : '<span class="hc-latest-badge-cmt">댓글</span>';

                $html .= '<li class="hc-latest-item">';
                $html .= '<div class="hc-latest-left">';
                $html .= $typeLabel;
                $html .= '<a href="' . $url . '" class="hc-latest-subject">' . htmlspecialchars($subject) . '</a>';

                if (strtotime($item->created_at) > time() - 86400) {
                    $html .= '<span class="hc-latest-new">N</span>';
                }
                $html .= '</div>';
                $html .= '<span class="hc-latest-date">' . $date . '</span>';
                $html .= '</li>';
            }
        }

        $html .= '</ul>';
        $html .= '</div>';

        return $html;
    }
}