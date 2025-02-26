<?php
/*
Plugin Name: Automatic Eye Catch
Plugin URI: https://manual.tonkatsutei.com/aec/
Description: アイキャッチを自動的に設定します。<a href="https://manual.tonkatsutei.com/aec/">ユーザーズマニュアル</a>
Author: ton活亭
Version: 0.5.3
Author URI: https://twitter.com/tonkatsutei

▼ update
コミット&GitHubにプッシュするだけ

▼ バージョンアップ内容
0.5.3
・insert_attachment_from_url()でE_ERRORの時にはnullを返すようにした

*/

declare(strict_types=1);

namespace tonkatsutei\automatic_eye_catch\main;

if (!defined('ABSPATH')) exit;
if (!defined('WP_MEMORY_LIMIT')) define('WP_MEMORY_LIMIT', '512M');

//ini_set("display_errors", 'On');
//error_reporting(E_ALL ^ E_DEPRECATED);

// 自動更新
require_once('plugin-update-checker-5.0/plugin-update-checker.php');

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$myUpdateChecker = PucFactory::buildUpdateChecker(
	'https://github.com/tonkatsutei-com/automatic_eye_catch/',
	__FILE__,
	'AEC'
);
$myUpdateChecker->setBranch('master');

// 本体読込
require_once('include/base.php');
