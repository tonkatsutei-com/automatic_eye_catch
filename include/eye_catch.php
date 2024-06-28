<?php

declare(strict_types=1);

namespace tonkatsutei\automatic_eye_catch\eye_catch;

if (!defined('ABSPATH')) exit;

use tonkatsutei\automatic_eye_catch\base\_common;
use tonkatsutei\automatic_eye_catch\no_image\_no_image;

class _eye_catch
{
	// 投稿時にアイキャッチを設定する
	public static function set_when_posts(object $post): void
	{
		$post_id = $post->ID;

		// アイキャッチが設定済みの際は抜ける
		$flug = has_post_thumbnail($post_id);
		if ($flug === true) {
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

		// 最初のimgタグ
		$re = _common::between('<img', '>', $html);
		if (count($re) === 0) {
			_no_image::set_no_image($post_id);
			return;
		}
		$img_tag = $re[0];

		// 画像のURL
		$re = _common::between("src='", "'", $img_tag);
		if (count($re) === 0) {
			_no_image::set_no_image($post_id);
			return;
		}
		$url = $re[0];

		// ファイル名
		$file_name = basename($url);

		if (!function_exists('media_handle_upload')) {
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/media.php');
		}

		// 外部サイトの画像かどうか
		if (strpos($url, wp_upload_dir()['url']) === false) {
			$gaibu_flug = true;
		} else {
			$gaibu_flug = false;
		}

		// WPにアップロードされた画像の場合
		if ($gaibu_flug === false) {
			// 1. 画像のIDを取得、未登録の場合は0が返ってくる
			$img_id = attachment_url_to_postid($url);

			// 2. 1で取得できなかった時はimg_tagから
			if ($img_id === 0) {
				$re = _common::between("class='wp-image-", "'/", $img_tag);
				if (count($re) > 0) {
					$img_id = (int)$re[0];
				}
			}

			// 3. 2でも取得できなかった時はwp_postmetaから検索
			if ($img_id === 0) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'postmeta';
				$sql = <<<EOD
				SELECT post_id
				FROM {$table_name}
				WHERE meta_value LIKE '%{$file_name}%'
				LIMIT 1
				EOD;
				$meta_post_id = $wpdb->get_results($sql);
				if (count($meta_post_id) > 0) {
					$img_id = $meta_post_id[0]->post_id;
				}
			}

			// 4. 3でも取得できなかった時は登録
			if ($img_id === 0) {
				$f = [
					'name' => $file_name,
					'tmp_name' => $url,
				];
				$img_id = media_handle_sideload($f);
			}
		}

		// 外部サイトの画像の場合はメディアに登録し画像のIDを取得
		if ($gaibu_flug) {
			$img_id = self::insert_attachment_from_url($url, $post_id, 30);
		}

		// IDを取得できなかった時はNoImageを指定して抜ける
		if ($img_id === 0 || $img_id === null) {
			_no_image::set_no_image($post_id);
			return;
		}

		//アイキャッチ画像に指定
		$res = set_post_thumbnail($post_id, $img_id);
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
