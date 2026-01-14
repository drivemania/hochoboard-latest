<?php
use App\Support\Hook;
use App\Support\PluginHelper;

class DiceState {
    public static $result = null;
}

Hook::add('before_comment_save', function($data) {
    if (strpos($data['content'], '/주사위') !== false) {
        
        DiceState::$result = [rand(1, 6), rand(1, 6)];

        $data['content'] = str_replace('/주사위', '', $data['content']);
    }

    return $data;
});

Hook::add('after_comment_save', function($id) {

        if (DiceState::$result !== null) {
            list($r1, $r2) = DiceState::$result;

            $diceHtml = '
            <div class="hc-dice-box">
                <span class="hc-dice-icon">🎲</span>
                <span class="hc-dice-text">주사위를 굴려 <strong>'.$r1.', '.$r2.'</strong>이(가) 나왔습니다!</span>
            </div>';
            PluginHelper::saveCommentMeta('hocho-diceroll', $id, 'result', $diceHtml);
            
            DiceState::$result = null;
        }

});

Hook::add('layout_head', function() {
    echo '<style>
        .hc-dice-box {
            display: inline-block;
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.9em;
            color: #374151;
            margin-top: 5px;
        }
        .hc-dice-icon { font-size: 1.2em; margin-right: 5px; }
        .hc-dice-text strong { color: #4f46e5; font-size: 1.1em; }
    </style>';
});