<?php
use WPIDE\App\AppConfig;

$editable = AppConfig::get('file.editable');
if(!empty($editable)) {
    AppConfig::update('editor.editable', $editable);
    AppConfig::delete('file.editable');
}
