<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Open Graph Support
 *
 * @package OpenGraph
 * @version 0.1.0
 * @author Manic Rabbit
 * @link https://manicrabbit.com
 */

class OpenGraph_Plugin implements Typecho_Plugin_Interface
{
    public static function activate() {
        Typecho_Plugin::factory('Widget_Archive')->header = array('OpenGraph_Plugin', 'header');
        return _t("已为社交平台提供 Meta 数据支持");
    }

    public static function deactivate() {}
    public static function config(Typecho_Widget_Helper_Form $form) {}
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    public static function header($header, Widget_Archive $archive) {
        $content = str_replace(array(' ', '\n', '\r', '　'), '', strip_tags($archive->content));
        $output = "";

        $headers = array(
            'url' => $archive->permalink,
            'title' => $archive->title,
            'description' => mb_strlen($content) < 140 ? $content : sprintf("%s...", mb_substr($content, 0, 140)),
            'type' => 'article',
            'author' => isset($archive->author) && $archive->author ? $archive->author->name : ''
        );

        if (preg_match("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpeg|\.png|\.jpg]))[\'|\"].*?[\/]?>/", $archive->content, $matches) !== 0) {
            list($html, $url) = $matches;
            $headers['image'] = $url;
        }

        foreach ($headers as $property => $content) {
            if (!is_null($content)) {
                $output .= sprintf('<meta property="og:%s" content="%s" />', $property, $content) . "\n";
            }
        }

        echo $output;
    }
}