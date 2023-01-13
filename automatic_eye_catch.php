<?php
/* 
Plugin Name: Automatic Eye Catch - Beta
Plugin URI: https://manual.tonkatsutei.com/aec/
Description: アイキャッチを自動的に設定します。<br><a href="https://manual.tonkatsutei.com/aec/">ユーザーズマニュアル</a>
Author: ton活亭
Version: 0.3.1
Author URI: https://twitter.com/tonkatsutei
*/

declare(strict_types=1);

if (!defined('ABSPATH')) exit;
@define('WP_MEMORY_LIMIT', '256M');

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

// バージョン履歴
$version_history = <<<EOD
[Ver.0.3.1] 2023/01/13
・説明文にマニュアルのリンクを入れた

[Ver.0.3.0] 2023/01/13
・GitHub/tonkatsutei-com にて公開
EOD;
