<?php

declare(strict_types=1);


namespace tonkatsutei\automatic_eye_catch\eye_catch;

if (!defined('ABSPATH')) exit;

use tonkatsutei\automatic_eye_catch\base\_options;
use tonkatsutei\automatic_eye_catch\base\_common;

class _eye_catch
{
    // 投稿時にアイキャッチを設定する
    public static function set_when_posts(object $post): void
    {
        // 対象記事のID
        $post_id = $post->ID;

        // アイキャッチが設定済みの際は抜ける
        $flug = has_post_thumbnail($post_id);
        if ($flug === true) {
            _options::update("error", "has post thumbnail");
            return;
        }

        // 記事のHTML
        $html = $post->post_content;

        // 整形
        $html = str_replace('"', "'", $html);
        $html = str_replace("\r\n", "\n", $html);
        $html = str_replace("\r", "\n", $html);
        $html = str_replace("\n", " ", $html);
        $html = str_replace("  ", " ", $html);
        _options::update("html", $html);

        // 最初のimgタグ
        $re = _common::between('<img', '>', $html);
        if (count($re) === 0) {
            _options::update("no img_tag", $html);
            return;
        }
        _options::update("img_tag_re", serialize($re));
        $img_tag = $re[0];
        _options::update("img_tag", $img_tag);

        // 画像のURL
        $re = _common::between("src='", "'", $img_tag);
        if (count($re) === 0) {
            _options::update("no url", $img_tag);
            return;
        }
        $url = $re[0];
        _options::update("url", $url);

        if (!function_exists('media_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        // 外部サイトの画像かどうか
        _options::update("wp_upload_dir", wp_upload_dir()['url']);
        if (strpos($url, wp_upload_dir()['url']) === false) {
            $gaibu_flug = true;
        } else {
            $gaibu_flug = false;
        }

        // WPにアップロードされた画像の場合
        if ($gaibu_flug === false) {
            // 1. 画像のIDを取得、未登録の場合は0が帰る
            $img_id = attachment_url_to_postid($url);

            // 2. 1で取得できなかった時はimg_tagから
            if ($img_id === 0) {
                $re = _common::between("class='wp-image-", "'/", $img_tag);
                if (count($re) === 0) {
                    _options::update("no wp-image", $img_tag);
                    return;
                } else {
                    $img_id = (int)$re[0];
                }
            }
            _options::update("added img_id", (string)$img_id);

            // 3. 1-2で取得できなかった時は登録
            $file_name = basename($url);
            _options::update("file_name", $file_name);
            if ($img_id === 0) {
                $f = [
                    'name' => $file_name,
                    'tmp_name' => $url,
                ];
                $img_id = media_handle_sideload($f);
                _options::update("add img_id", (string)$img_id);
            }
        }



        // メディアに登録し画像のIDを取得（外部サイトの画像）
        if ($gaibu_flug) {
            $img_id = self::insert_attachment_from_url($url, $post_id, 30);
        }

        // IDを取得できなかった時は抜ける
        if ($img_id === 0 || $img_id === null) {
            _options::update("no img_id", "error");
            return;
        }

        //アイキャッチ画像に指定
        $res = set_post_thumbnail($post_id, $img_id);

        _options::update("img_id", (string)$res);
    }


    /**
     * 指定の URL の画像をダウンロードして、メディア ライブラリへ登録します。
     * https://xakuro.com/blog/wordpress/1253/
     * @param string $url
     * @param int $post_id 投稿 ID。
     * @param int $timeout タイムアウト。デフォルトは 30 秒。
     * @return int|WP_Error 添付ファイル ID。失敗した場合の WP_Error オブジェクト。
     */
    public static function insert_attachment_from_url(string $url, int $post_id = 0, int $timeout = 30): ?int
    {
        $tmp_file = download_url($url, $timeout);
        if (is_wp_error($tmp_file)) {
            return null;
        }

        $file = array(
            'name' => basename($url),
            'tmp_name' => $tmp_file,
        );
        $attachment_id = media_handle_sideload($file, $post_id);
        if (is_wp_error($attachment_id)) {
            @unlink($tmp_file);
        }

        return $attachment_id;
    }
}
