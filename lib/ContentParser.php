<?php

class ContentParser
{
    protected $base_path;

    public function __construct($base_path = '')
    {
        $this->base_path = $base_path;
    }

    public function parse($content)
    {
        $content = $content ?? '';

        $content = preg_replace_callback(
            '/@hc_menu\((.*?)\)/',
            function ($matches) {
                $arg = strip_tags($matches[1]);
                $arg = html_entity_decode($arg, ENT_QUOTES | ENT_HTML5);
                $arg = trim($arg); 
                return \Widget::menu($this->base_path, $arg);
            },
            $content
        );

        $content = preg_replace_callback(
            '/@hc_login\((.*?)\)/',
            function ($matches) {
                $arg = strip_tags($matches[1]);
                $arg = html_entity_decode($arg, ENT_QUOTES | ENT_HTML5);
                $arg = trim($arg); 
                return \Widget::login($this->base_path, $arg);
            },
            $content
        );

        $content = preg_replace_callback(
            '/@hc_latestPost\((.*?)\)/',
            function ($matches) {
                $cleanString = strip_tags($matches[1]);
                $cleanString = html_entity_decode($cleanString, ENT_QUOTES | ENT_HTML5);
                $rawArgs = explode(',', $cleanString);
                $args = array_map(function($arg) {
                    $arg = preg_replace('/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '', $arg);
                    return trim($arg, "'\"");
                }, $rawArgs);
                
                $page = $args[0] ?? 10;
                $subLimit = $args[1] ?? 20;
                $slug = $args[2] ?? null;

                return \Widget::latestPosts($this->base_path, $page, $subLimit, $slug);
            },
            $content
        );

        $content = preg_replace_callback(
            '/Helper::auto_link\((.*?)\)/',
            function ($matches) {
                $arg = strip_tags($matches[1]);
                $arg = html_entity_decode($arg, ENT_QUOTES | ENT_HTML5);
                $arg = trim($arg); 
                return \Helper::auto_link($arg);
            },
            $content
        );

        $content = preg_replace_callback(
            '/Helper::auto_hashtag\((.*?)\)/',
            function ($matches) {
                $cleanString = strip_tags($matches[1]);
                $cleanString = html_entity_decode($cleanString, ENT_QUOTES | ENT_HTML5);
                $rawArgs = explode(',', $cleanString);
                $args = array_map(function($arg) {
                    $arg = preg_replace('/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '', $arg);
                    return trim($arg, "'\"");
                }, $rawArgs);
                if (method_exists('Helper', 'auto_link')) {
                    $text = trim($args[0]);
                    $currentUrl = trim($args[1]);
                    return \Helper::auto_hashtag($text, $currentUrl);
                }
                return '';
            },
            $content
        );
        
        $content = preg_replace_callback(
            '/Helper::getMyMainChr\((.*?)\)/',
            function ($matches) {
                $cleanString = strip_tags($matches[1]);
                $cleanString = html_entity_decode($cleanString, ENT_QUOTES | ENT_HTML5);
                $rawArgs = explode(',', $cleanString);
                $args = array_map(function($arg) {
                    $arg = preg_replace('/^[\p{Z}\s]+|[\p{Z}\s]+$/u', '', $arg);
                    return trim($arg, "'\"");
                }, $rawArgs);
                if (method_exists('Helper', 'getMyMainChr')) {
                    $mid = $args[0];
                    $gid = $args[1];
                    return \Helper::getMyMainChr($mid, $gid);
                }
                return '';
            },
            $content
        );

        return $content;
    }
}