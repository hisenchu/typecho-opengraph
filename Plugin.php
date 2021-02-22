<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 为 Typecho 增加 Open Graph 协议的支持
 *
 * @package OpenGraph
 * @version 0.1.1
 * @author Manic Rabbit
 * @link https://manicrabbit.com
 */

class OpenGraph_Plugin implements Typecho_Plugin_Interface
{
    const FIELD_NONE = 'None';

    public static function activate() {
        Typecho_Plugin::factory('Widget_Archive')->header = array('OpenGraph_Plugin', 'handler');

        return _t("已为社交平台提供 Open Graph XML 数据支持");
    }

    public static function deactivate() {
        return _t("已取消 Open Graph XML 的数据支持");
    }

    public static function config(Typecho_Widget_Helper_Form $form) {
        Typecho_Widget::widget('Widget_Contents_Post_Edit')->to($post);
        $fields = $post->getDefaultFieldItems();

        $cover = new Typecho_Widget_Helper_Form_Element_Text(
            'cover',
            NULL,
            NULL,
            _t('网站首页访问时图片地址'),
            _t(htmlspecialchars('设置网站首页 <meta property="image" content="..."> 图片地址'))
        );

        $form->addInput($cover->addRule("url", _t('必须是 URL 地址')));

        if (count($fields)) {
            $fields = array_keys($fields);
            $customFields = array();
            $none = array( self::FIELD_NONE => '不设置' );

            foreach($fields as $field) {
                $customFields[$field] = $field;
            }

            array_splice($customFields, 0, 0, $none);

            $field = new Typecho_Widget_Helper_Form_Element_Select(
                'field',
                $customFields,
                NULL,
                _t('自定义主题中焦点图字段'),
                _t('如果主题有焦点图自定义字段，请选择主题中的字段以便插件更好的获取图片路径')
            );

            $form->addInput($field);
        }
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    public static function execute() {}

    protected static function getAttachments($archive, $customField = NULL) {
        if ($customField && $customField !== NULL && $customField != self::FIELD_NONE) {
            return $archive->fields->$customField;
        }

        if (preg_match("/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpeg|\.png|\.jpg]))[\'|\"].*?[\/]?>/", $archive->content, $matches) !== 0) {
            list($html, $url) = $matches;
            return $url;
        }
    }

    public static function handler($header, Widget_Archive $archive) {
        $options = Typecho_Widget::widget('Widget_Options');

        if ( ! $options ) return;

        $content = $archive->is('index')
                ? strip_tags($options->description)
                : str_replace(array(' ', '\n', '\r', '　'), '', strip_tags($archive->content));

        $url = $archive->is('index') ? $options->siteUrl : $archive->permalink;
        $title =  $archive->is('index') ? strip_tags($options->title) : $archive->title;

        $archiveType = ($archive->is('index') ? 'website' : 'article');
        $self = Typecho_Widget::widget('Widget_Options')->plugin('OpenGraph');

        $headers = array(
            'url' => $url,
            'title' => $title,
            'description' => $content,
            'type' => $archiveType,
            'author' => isset($archive->author) && $archive->author ? $archive->author->name : '',
            'image' => self::getAttachments($archive, $self->field)
        );

        if ($archive->is('index')) {
            $headers['image'] = $self->cover;
        }

        $output = "";

        foreach ($headers as $property => $content) {
            if (!empty($content)) {
                $output .= sprintf('<meta property="og:%s" content="%s" />', $property, $content) . "\n";
            }
        }

        echo $output;
    }
}