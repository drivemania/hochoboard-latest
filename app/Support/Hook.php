<?php
namespace App\Support;

class Hook {
    private static $listeners = [];

    public static function add($hookName, $callback, $priority = 10) {
        self::$listeners[$hookName][] = [
            'callback' => $callback,
            'priority' => $priority
        ];
    }

    public static function trigger($hookName, ...$args) {
        if (empty(self::$listeners[$hookName])) return;

        usort(self::$listeners[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach (self::$listeners[$hookName] as $listener) {
            call_user_func_array($listener['callback'], $args);
        }
    }

    public static function filter($hookName, $value, ...$args) {
        if (empty(self::$listeners[$hookName])) return $value;

        usort(self::$listeners[$hookName], fn($a, $b) => $a['priority'] <=> $b['priority']);

        foreach (self::$listeners[$hookName] as $listener) {
            $value = call_user_func_array($listener['callback'], array_merge([$value], $args));
        }

        return $value;
    }
}