<?php
defined( 'ABSPATH' ) || exit;

return [
    [
        'route' => [
            'GET', '/getchangelog', '\WPIDE\App\Controllers\ChangelogController@get',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
        ],
    ],
    [
        'route' => [
            'GET', '/', '\WPIDE\App\Controllers\ViewController@index',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
        ],
    ],
    [
        'route' => [
            'GET', '/getuser', '\WPIDE\App\Controllers\AuthController@getUser',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
        ],
    ],
    [
        'route' => [
            'GET', '/getconfig', '\WPIDE\App\Controllers\ConfigController@getConfig',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
        ],
    ],
    [
        'route' => [
            'POST', '/updateconfig', '\WPIDE\App\Controllers\ConfigController@updateConfig',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
        ],
    ],
    [
        'route' => [
            'POST', '/changedir', '\WPIDE\App\Controllers\FileManager\FileController@changeDirectory',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/getdir', '\WPIDE\App\Controllers\FileManager\FileController@getDirectory',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/getdirsize', '\WPIDE\App\Controllers\FileManager\FileController@getDirectorySize',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/copyitems', '\WPIDE\App\Controllers\FileManager\FileController@copyItems',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write',
        ],
    ],
    [
        'route' => [
            'POST', '/moveitems', '\WPIDE\App\Controllers\FileManager\FileController@moveItems',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write',
        ],
    ],
    [
        'route' => [
            'POST', '/renameitem', '\WPIDE\App\Controllers\FileManager\FileController@renameItem',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write',
        ],
    ],
    [
        'route' => [
            'POST', '/zipitems', '\WPIDE\App\Controllers\FileManager\FileController@zipItems',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write', 'zip',
        ],
    ],
    [
        'route' => [
            'POST', '/unzipitem', '\WPIDE\App\Controllers\FileManager\FileController@unzipItem',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write', 'zip',
        ],
    ],
    [
        'route' => [
            'POST', '/deleteitems', '\WPIDE\App\Controllers\FileManager\FileController@deleteItems',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write',
        ],
    ],
    [
        'route' => [
            'POST', '/createnew', '\WPIDE\App\Controllers\FileManager\FileController@createNew',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write',
        ],
    ],
    [
        'route' => [
            'GET', '/upload', '\WPIDE\App\Controllers\FileManager\UploadController@chunkCheck',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'upload',
        ],
    ],
    [
        'route' => [
            'POST', '/upload', '\WPIDE\App\Controllers\FileManager\UploadController@upload',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'upload',
        ],
    ],
    [
        'route' => [
            'POST', '/revertimage', '\WPIDE\App\Controllers\FileManager\ImageController@revert',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'download',
        ],
    ],
    [
        'route' => [
            'GET', '/getimagestate', '\WPIDE\App\Controllers\FileManager\ImageController@getState',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'download',
        ],
    ],
    [
        'route' => [
            'GET', '/downloadimage', '\WPIDE\App\Controllers\FileManager\ImageController@download',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'download',
        ],
    ],
    [
        'route' => [
            'GET', '/download', '\WPIDE\App\Controllers\FileManager\DownloadController@download',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'download',
        ],
    ],
    [
        'route' => [
            'GET', '/batchdownloadqueue', '\WPIDE\App\Controllers\FileManager\DownloadController@batchDownloadQueue',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'download', 'batchdownload',
        ],
    ],
    [
        'route' => [
            'POST', '/batchdownload', '\WPIDE\App\Controllers\FileManager\DownloadController@batchDownloadChunks',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'download', 'batchdownload',
        ],
    ],
    [
        'route' => [
            'DELETE', '/batchdownload', '\WPIDE\App\Controllers\FileManager\DownloadController@batchDownloadCancelled',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'download', 'batchdownload',
        ],
    ],
    [
        'route' => [
            'GET', '/batchdownload', '\WPIDE\App\Controllers\FileManager\DownloadController@batchDownloadStart',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'download', 'batchdownload',
        ],
    ],
    [
        'route' => [
            'POST', '/savecontent', '\WPIDE\App\Controllers\FileManager\FileController@saveContent',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write',
        ],
    ],
    [
        'route' => [
            'GET', '/autosave/get', '\WPIDE\App\Controllers\FileManager\AutoSaveController@get',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/autosave/save', '\WPIDE\App\Controllers\FileManager\AutoSaveController@save',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'POST', '/autosave/delete', '\WPIDE\App\Controllers\FileManager\AutoSaveController@delete',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'GET', '/config/constants', '\WPIDE\App\Controllers\ConfigManager\ConfigController@getConfigConstants',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/config/constants/save', '\WPIDE\App\Controllers\ConfigManager\ConfigController@saveConfigConstant',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'POST', '/config/constants/delete', '\WPIDE\App\Controllers\ConfigManager\ConfigController@deleteConfigConstant',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'POST', '/db/gettables', '\WPIDE\App\Controllers\DbManager\DatabaseController@getTables',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/db/gettablerows', '\WPIDE\App\Controllers\DbManager\DatabaseController@getTableRows',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/db/gettablestructure', '\WPIDE\App\Controllers\DbManager\DatabaseController@getTableStructureRows',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read',
        ],
    ],
    [
        'route' => [
            'POST', '/db/savetablerow', '\WPIDE\App\Controllers\DbManager\DatabaseController@saveTableRow',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'POST', '/db/deletetablerows', '\WPIDE\App\Controllers\DbManager\DatabaseController@deleteTableRows',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'POST', '/db/droptable', '\WPIDE\App\Controllers\DbManager\DatabaseController@dropTable',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'POST', '/db/emptytable', '\WPIDE\App\Controllers\DbManager\DatabaseController@emptyTable',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'POST', '/db/createtable', '\WPIDE\App\Controllers\DbManager\DatabaseController@createTable',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'GET', '/db/exporttable', '\WPIDE\App\Controllers\DbManager\DatabaseController@exportTable',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
    [
        'route' => [
            'GET', '/db/exportdb', '\WPIDE\App\Controllers\DbManager\DatabaseController@exportDatabase',
        ],
        'roles' => [
            'user', 'admin',
        ],
        'permissions' => [
            'read', 'write'
        ],
    ],
];
