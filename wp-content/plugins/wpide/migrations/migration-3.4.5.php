<?php
use WPIDE\App\AppConfig;
use const WPIDE\Constants\CONTENT_DIR;

$root = AppConfig::get('file.root');
$content_dir = basename(CONTENT_DIR);

if(!str_contains($root, $content_dir)) {

    $root = str_replace(ABSPATH, "/", CONTENT_DIR.$root);

    AppConfig::update('file.root', $root);
}

