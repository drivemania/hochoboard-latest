<?php
namespace App\Support;

use Illuminate\Database\Capsule\Manager as DB;

class PluginHelper {

    public static function save(string $targetType, int $targetId, string $pluginName, string $key, $value) {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        DB::table('plugin_meta')->updateOrInsert(
            [
                'target_type' => $targetType,
                'target_id'   => $targetId,
                'plugin_name' => $pluginName,
                'key_name'    => $key
            ],
            ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
        );
    }

    public static function get(string $targetType, int $targetId, string $pluginName, string $key) {
        $row = DB::table('plugin_meta')
                 ->where('target_type', $targetType)
                 ->where('target_id', $targetId)
                 ->where('plugin_name', $pluginName)
                 ->where('key_name', $key)
                 ->first();
                 
        if (!$row) return null;

        $decoded = json_decode($row->value, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $row->value;
    }

    public static function saveCommentMeta(string $pluginName, int $commentId, string $key, $value) {
        self::save('comment', $commentId, $pluginName, $key, $value);
    }
    public static function getCommentMeta(string $pluginName, int $commentId, string $key) {
        return self::get('comment', $commentId, $pluginName, $key);
    }

    public static function saveDocumentMeta(string $pluginName, int $postId, string $key, $value) {
        self::save('document', $postId, $pluginName, $key, $value);
    }
    public static function getPostMeta(string $pluginName, int $postId, string $key) {
        return self::get('document', $postId, $pluginName, $key);
    }
        public static function saveUserMeta(string $pluginName, int $userId, string $key, $value) {
        self::save('user', $userId, $pluginName, $key, $value);
    }
}