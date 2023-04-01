<?php

use const WPIDE\Constants\CONTENT_DIR;

defined('ABSPATH') || exit;

return apply_filters('wpide_config', [
    'general' => [
        'dark_mode' => [
            'type' => 'bool',
            'label' => __('Dark Mode', 'wpide'),
            'default' => false
        ],
        'skin' => [
            'type' => 'swatches',
            'label' => __('Theme', 'wpide'),
            'options' => [
                'default' => 'Default',
                'blue' => 'Blue',
                'egyptian' => 'Egyptian',
                'green' => 'Green',
                'purple' => 'Purple',
                'red' => 'Red'
            ],
            'default' => 'default'
        ],
    ],
    'file' => [
        'advanced_mode' => [
            'type' => 'bool',
            'label' => __('Advanced Mode', 'wpide'),
            'desc_long' => sprintf(__('When enabled, all files and folders will be available for editing including core wordpress files and the wp-config.php unless they are filtered out manually within the settings. %1$sEnable Advanced Mode at your own risk!%2$s Please note that the %1$sFile Recovery Wizard might not work properly%2$s if you ever save core PHP file that contains errors and break the site. However, backups will still be available, so you can recover corrupted files manually via FTP.', 'wpide'), '<strong class="text-danger">', '</strong>'),
            'default' => false,
            'premium' => true
        ],
        'root' => [
            'type' => 'folders',
            'label' => __('Root Path', 'wpide'),
            'default' => '/'.basename(CONTENT_DIR)
        ],
        'overwrite_on_upload' => [
            'type' => 'bool',
            'label' => __('Override on upload', 'wpide'),
            'default' => false
        ],
        'calc_dir_size' => [
            'type' => 'select',
            'label' => __('Calculate folder size', 'wpide'),
            'desc' => __('Folder size will be cached for faster load on subsequent requests.', 'wpide'),
            'options' => [
                'ondemand' => __('On Demand', 'wpide'),
                'onload' => __('On load', 'wpide'),
                'disabled' => __('Disabled', 'wpide'),
            ],
            'default' => 'ondemand',
        ],
        'backups_max_days' => [
            'type' => 'number',
            'label' => __('Backups max days', 'wpide'),
            'desc' => __('Remove backup folders older than X days', 'wpide'),
            'default' => 5
        ],
        'upload_max_size' => [
            'type' => 'number',
            'label' => __('Max upload size (MB)', 'wpide'),
            'default' => 100 * 1024 * 1024, // 100MB
        ],
        'upload_chunk_size' => [
            'type' => 'number',
            'label' => __('Upload chunk size (MB)', 'wpide'),
            'default' => 1 * 1024 * 1024, // 1MB
        ],
        'upload_simultaneous' => [
            'type' => 'number',
            'label' => __('Simultaneous uploads', 'wpide'),
            'default' => 3,
        ],
        'default_archive_name' => [
            'type' => 'text',
            'label' => __('Default archive name', 'wpide'),
            'default' => 'archive.zip',
        ],
        'autoplay_media' => [
            'type' => 'bool',
            'label' => __('Auto play media files, when possible.', 'wpide'),
            'desc' => __('Chromium browsers do not allow autoplay, unless user interaction is detected on the page.', 'wpide'),
            'default' => false
        ],
        'search_simultaneous' => [
            'type' => 'number',
            'label' => __('Simultaneous search', 'wpide'),
            'default' => 5,
        ],
        'filter_entries' => [
            'type' => 'text',
            'repeater' => true,
            'label' => __('Entries filter', 'wpide'),
            'desc' => __('Enter partial or full path. To exclude folders, add a trailing slash. Note: WordPress core files as well as important WPide folders and files are hidden automatically.', 'wpide'),
            'default' => [
                '.DS_Store',
                '.tmb/',
                '.idea/',
                '.codekit-cache/',
                '.git/',
                '.sass-cache/',
                'Recycle.bin/',
                '@eaDir/',
                '#recycle/',
                'node_modules/'
            ]
        ]
    ],
    'editor' => [
        'autosave' => [
            'type' => 'bool',
            'label' => __('Auto save draft', 'wpide'),
            'desc_long' => sprintf(__('While editing, files will automatically be saved every X seconds to a temporary draft file. Original files are not affected. If you ever close or refresh the page by mistake without manually saving a file, the next time you open it, a %1$sFile Recovery%2$s modal will display allowing you to restore from the auto saved file. You can also view and compare both files differences using the %1$sQuick Diff Viewer%2$s.', 'wpide'), '<span class="text-primary">', '</span>'),
            'default' => false,
            'premium' => true
        ],
        'autosave_interval' => [
            'type' => 'range',
            'label' => __('Auto save interval (sec)', 'wpide'),
            'attr' => [
                'min' => 5,
                'max' => 120,
                'step' => 5
            ],
            'default' => 10,
            'conditions'=> [
                [
                    'id'=> 'editor.autosave',
                    'value'=> true
                ]
            ],
            'premium' => true
        ],
        'editable' => [
            'type' => 'select',
            'multiple' => true,
            'label' => __('Editable files', 'wpide'),
            'desc' => __('Select one of multiple extensions by pressing the shift button.', 'wpide'),
            'options' => apply_filters('wpide_editable_ext_options', [
                '.' => 'File without extension',
                'txt' => '.txt',
                'css' => '.css',
                'scss' => '.scss',
                'less' => '.less',
                'js' => '.js',
                'html' => '.html',
                'php' => '.php',
                'json' => '.json',
                'yml' => '.yml',
                'yaml' => '.yaml',
                'xml' => '.xml',
                'svg' => '.svg',
                'md' => '.md',
                'log' => '.log',
                'htaccess' => '.htaccess'
            ]),
            'default' => ['.', 'txt', 'css', 'scss', 'less', 'js', 'html', 'php', 'json', 'yml', 'yaml', 'xml', 'svg', 'md', 'log', 'htaccess']
        ],
        'font_size' => [
            'type' => 'range',
            'label' => __('Font Size (px)', 'wpide'),
            'attr' => [
                'min' => 10,
                'max' => 20
            ],
            'default' => 12,
        ],
        'scroll_speed' => [
            'type' => 'range',
            'label' => __('Scroll Speed', 'wpide'),
            'attr' => [
                'min' => 1,
                'max' => 20
            ],
            'default' => 3,
        ],
        'hightlight_active_line' => [
            'type' => 'bool',
            'label' => __('Highlight Active Line', 'wpide'),
            'default' => false
        ],
        'behaviours_enabled' => [
            'type' => 'bool',
            'label' => __('Enable Behaviours', 'wpide'),
            'desc' => __('When enabled, the editor will auto close tags and brackets', 'wpide'),
            'default' => true
        ],
        'use_soft_tabs' => [
            'type' => 'bool',
            'label' => __('Use Soft Tabs', 'wpide'),
            'desc' => __('When enabled, a tab key press will insert spaces instead of the tab character', 'wpide'),
            'default' => false
        ],
        'tab_size' => [
            'type' => 'range',
            'label' => __('Tab Size', 'wpide'),
            'attr' => [
                'min' => 1,
                'max' => 8
            ],
            'default' => 4
        ]
    ],
    'db' => [
        'per_page' => [
            'type' => 'select',
            'label' => __('Items per page default', 'wpide'),
            'options' => [
                '50' => '50',
                '100' => '100',
                '500' => '500',
                '1000' => '1000'
            ],
            'default' => '50',
        ]
    ]
]);
