<?php

declare(strict_types=1);

namespace tonkatsutei\automatic_eye_catch\set_to_posts_unset;

use tonkatsutei\automatic_eye_catch\eye_catch\_eye_catch;
use tonkatsutei\automatic_eye_catch\base\_options;

if (!defined('ABSPATH')) exit;
class _set_to_posts_unset
{
	public static function set_to_posts_unset(): void
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'posts';

		// 公開済みでアイキャッチが未設定の投稿を検索
		$sql = <<<EOD
			SELECT      id
			FROM        {$table_name}
			WHERE       post_type = 'post'
			AND         post_status in ('publish', 'future')
			AND         not exists ( 
									SELECT  * 
									FROM    wp_postmeta 
									WHERE   wp_postmeta.meta_key = '_thumbnail_id'
									AND     wp_postmeta.post_id  = wp_posts.id )
			ORDER BY    post_date desc
			LIMIT       5
		EOD;
		$dsn = $wpdb->get_results($sql);
		_options::update('sql', $sql);

		_options::update('test', serialize($dsn));
		foreach ($dsn as $i) {
			$post_id = (int)$i->id;
			$post    = get_post($post_id);
			_eye_catch::set_when_posts($post_id, $post);
		}
	}
}
