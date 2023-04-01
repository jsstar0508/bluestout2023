<?php
/*
 * WPide - Fatal Error Handler - Drop-In
 * Takes care of catching errors and exception after editing php files using WPide file editor and offers an easy way to recover erroneous files by restoring them from saved backups
*/

if (!class_exists('WPIDE_Error_Handler')) {

    class WPIDE_Error_Handler
    {

        protected static $slug = 'wpide';
        protected static $backupDir = '/wpide/backups/';

        public static function boot()
        {

            if (version_compare(PHP_VERSION, '7.4.0', '>=')) {

                register_shutdown_function([__CLASS__, 'shutdownHandler']);
                set_exception_handler([__CLASS__, 'exceptionHandler']);
                set_error_handler([__CLASS__, 'errorHandler']);
            }
        }

        public static function isPluginScreen(): bool
        {

            $screen = function_exists('get_current_screen') ? get_current_screen() : null;

            if(!empty($screen)) {
                return strpos($screen->base, 'page_' . self::$slug) !== false;
            }

            if(!empty($_GET['page'])) {
                $page = sanitize_text_field($_GET['page']);
                return strpos($page, self::$slug) !== false;
            }

            return false;
        }

        public static function shutdownHandler()
        {

            $error = error_get_last();

            if ($error) {
                self::errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
            }
        }

        public static function exceptionHandler(Throwable $exception)
        {
            self::errorHandler(-1, $exception->getMessage(), $exception->getFile(), $exception->getLine());
        }

        public static function errorHandler($error_type, $error_message, $error_file, $error_line)
        {

            if (!$error_type) {
                return;
            }

            $fatal = false;

            switch ($error_type) {

                case -1:
                case E_ERROR:
                case E_USER_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_RECOVERABLE_ERROR:
                    $fatal = true;
                    break;
            }

            switch ($error_type) {
                case -1: // -1 //
                    $typestr = 'EXCEPTION';
                    break;
                case E_ERROR: // 1 //
                    $typestr = 'E_ERROR';
                    break;
                case E_WARNING: // 2 //
                    $typestr = 'E_WARNING';
                    break;
                case E_PARSE: // 4 //
                    $typestr = 'E_PARSE';
                    break;
                case E_NOTICE: // 8 //
                    $typestr = 'E_NOTICE';
                    break;
                case E_CORE_ERROR: // 16 //
                    $typestr = 'E_CORE_ERROR';
                    break;
                case E_CORE_WARNING: // 32 //
                    $typestr = 'E_CORE_WARNING';
                    break;
                case E_COMPILE_ERROR: // 64 //
                    $typestr = 'E_COMPILE_ERROR';
                    break;
                case E_COMPILE_WARNING: // 128 //
                    $typestr = 'E_COMPILE_WARNING';
                    break;
                case E_USER_ERROR: // 256 //
                    $typestr = 'E_USER_ERROR';
                    break;
                case E_USER_WARNING: // 512 //
                    $typestr = 'E_USER_WARNING';
                    break;
                case E_USER_NOTICE: // 1024 //
                    $typestr = 'E_USER_NOTICE';
                    break;
                case E_STRICT: // 2048 //
                    $typestr = 'E_STRICT';
                    break;
                case E_RECOVERABLE_ERROR: // 4096 //
                    $typestr = 'E_RECOVERABLE_ERROR';
                    break;
                case E_DEPRECATED: // 8192 //
                    $typestr = 'E_DEPRECATED';
                    break;
                case E_USER_DEPRECATED: // 16384 //
                    $typestr = 'E_USER_DEPRECATED';
                    break;

            }

            $message = '<strong>' . $typestr . ': </strong>' . $error_message . ' in <strong>' . $error_file . '</strong> on line <strong>' . $error_line . '</strong><br/><br/>';

            //Logging error on php file error log...
            if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
                error_log(strip_tags($message), 0);
            }

            if ($fatal) {

                $lockfile = self::backupDir().'.lock';
                $backup_file = self::findBackupFile($error_file);

                // If backup exists
                if (!empty($backup_file) && file_exists($backup_file)) {

                    if (ob_get_length()) ob_end_clean();

                    // If on admin side, show file recovery wizard
                    if (is_admin()) {
                        $recover = !empty($_GET['recover']);
                        if ($recover) {

                            $success = self::recoverFile($error_file, $backup_file);

                            if(file_exists($lockfile)) {
                                @unlink($lockfile);
                            }

                            wp_send_json([
                                'success' => $success
                            ]);

                        } else {

                            self::recoverTemplate($typestr, $error_message, $error_file, $error_line, $backup_file);

                            file_put_contents($lockfile, '1');
                        }

                        // If on the frontend, try to recover the file silently
                    } else if(!file_exists($lockfile)){

                        if(file_exists($lockfile)) {
                            @unlink($lockfile);
                        }

                        $success = self::recoverFile($error_file, $backup_file);

                        // redirect home on success
                        if ($success) {

                            die('<meta http-equiv="refresh" content="0">');

                        } else {

                            // if failed, show error message
                            self::displayError($message);
                        }
                    }
                    die();
                }
            }

        }

        public static function displayError($message) {

            // Display error only if WP_DEBUG & WP_DEBUG_DISPLAY are enabled and not DOING_AJAX
            if(!self::isPluginScreen() && defined('WP_DEBUG') && WP_DEBUG && defined('WP_DEBUG_DISPLAY') && WP_DEBUG_DISPLAY && !defined('DOING_AJAX')) {
                printf('%s', $message);
            }
        }

        public static function backupDir(): string
        {

            $uploadsBaseDir = function_exists('wp_upload_dir') ? wp_upload_dir()['basedir'] : __DIR__.'/uploads/';

            return $uploadsBaseDir.self::$backupDir;
        }

        public static function findBackupFile($error_file): ?string
        {

            $backupFolders = scandir(self::backupDir());

            if(empty($backupFolders)) {
                return null;
            }

            // Get backup folders
            $backupFolders = array_filter($backupFolders, function ($folder) {

                if ($folder === '.' || $folder === '..') {
                    return false;
                }

                $date = date_create_from_format('Y-m-d', $folder);

                return $date !== false;
            });

            // Sort from latest to oldest
            sort($backupFolders);
            $backupFolders = array_reverse($backupFolders);

            // Loop and find the latest backup file
            foreach ($backupFolders as $folder) {

                $backup_file = self::backupDir() . $folder . '/' . str_replace(ABSPATH, "", $error_file);

                if (file_exists($backup_file)) {
                    return $backup_file;
                }
            }

            return null;
        }

        public static function recoverFile($error_file, $backup_file): bool
        {

            ob_start();
            $backup_file_content = file_get_contents($backup_file);
            $result = file_put_contents($error_file, $backup_file_content);
            ob_end_clean();

            return $result !== false;
        }

        public static function recoverTemplate($error_type, $error_message, $error_file, $error_line, $backup_file)
        {

            ?>
            <div class="container">
                <div class="error">
                    <div class="error-inner">
                        <h2><?php echo $error_type; ?></h2>
                        <h3>Ooh-oh, something went wrong after modifying this file:</h3>
                        <p class="error-file">
                            <code><strong>File</strong>
                                <span><?php echo str_replace(__DIR__, "", $error_file); ?></span></code>
                            <code class="error-message"><strong>Error</strong>
                                <span><?php echo $error_message; ?></span></code>
                            <code><strong>Line</strong> <span><?php echo $error_line; ?></span></code>
                            <code><strong>Backup</strong>
                                <span><?php echo str_replace(__DIR__, "", $backup_file); ?></span></code>
                        </p>
                        <p class="recovering-message">
                            <strong>WPIDE</strong> will try to recover the file for you using the latest saved backup!
                        </p>
                        <p>
                            <button class="button recover-btn"><span>Recover</span></button>
                            <button class="button refresh-btn"><span>Refresh</span></button>
                        </p>
                    </div>
                </div>
                <div class="stack-container">
                    <div class="card-container">
                        <div class="perspec" style="--spreaddist: 125px; --scaledist: .75; --vertdist: -25px;">
                            <div class="card">
                                <div class="writing">
                                    <div class="topbar">
                                        <div class="red"></div>
                                        <div class="yellow"></div>
                                        <div class="green"></div>
                                    </div>
                                    <div class="code">
                                        <ul></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-container">
                        <div class="perspec" style="--spreaddist: 100px; --scaledist: .8; --vertdist: -20px;">
                            <div class="card">
                                <div class="writing">
                                    <div class="topbar">
                                        <div class="red"></div>
                                        <div class="yellow"></div>
                                        <div class="green"></div>
                                    </div>
                                    <div class="code">
                                        <ul></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-container">
                        <div class="perspec" style="--spreaddist:75px; --scaledist: .85; --vertdist: -15px;">
                            <div class="card">
                                <div class="writing">
                                    <div class="topbar">
                                        <div class="red"></div>
                                        <div class="yellow"></div>
                                        <div class="green"></div>
                                    </div>
                                    <div class="code">
                                        <ul></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-container">
                        <div class="perspec" style="--spreaddist: 50px; --scaledist: .9; --vertdist: -10px;">
                            <div class="card">
                                <div class="writing">
                                    <div class="topbar">
                                        <div class="red"></div>
                                        <div class="yellow"></div>
                                        <div class="green"></div>
                                    </div>
                                    <div class="code">
                                        <ul></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-container">
                        <div class="perspec" style="--spreaddist: 25px; --scaledist: .95; --vertdist: -5px;">
                            <div class="card">
                                <div class="writing">
                                    <div class="topbar">
                                        <div class="red"></div>
                                        <div class="yellow"></div>
                                        <div class="green"></div>
                                    </div>
                                    <div class="code">
                                        <ul></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-container">
                        <div class="perspec" style="--spreaddist: 0px; --scaledist: 1; --vertdist: 0px;">
                            <div class="card">
                                <div class="writing">
                                    <div class="topbar">
                                        <div class="red"></div>
                                        <div class="yellow"></div>
                                        <div class="green"></div>
                                    </div>
                                    <div class="code">
                                        <ul></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <style>
                body {
                    background: #f5f6fa;
                    -webkit-font-smoothing: antialiased;
                    -moz-osx-font-smoothing: grayscale;
                }

                body,
                html {
                    padding: 0;
                    margin: 0;
                    font-family: 'Quicksand', sans-serif;
                    font-weight: 400;
                    overflow: hidden;
                }

                .writing {
                    width: 320px;
                    height: 200px;
                    background-color: #3f3f3f;
                    border: 1px solid #bbbbbb;
                    border-radius: 6px 6px 4px 4px;
                    position: relative;
                }

                .writing .topbar {
                    position: absolute;
                    width: 100%;
                    height: 12px;
                    background-color: #f1f1f1;
                    border-top-left-radius: 4px;
                    border-top-right-radius: 4px;
                }

                .writing .topbar div {
                    height: 6px;
                    width: 6px;
                    border-radius: 50%;
                    margin: 3px;
                    float: left;
                }

                .writing .topbar div.green {
                    background-color: #60d060;
                }

                .writing .topbar div.red {
                    background-color: red;
                }

                .writing .topbar div.yellow {
                    background-color: #e6c015;
                }

                .writing .code {
                    padding: 15px;
                }

                .writing .code ul {
                    list-style: none;
                    margin: 0;
                    padding: 0;
                }

                .writing .code ul li {
                    background-color: #9e9e9e;
                    width: 0;
                    height: 7px;
                    border-radius: 6px;
                    margin: 10px 0;
                }

                .container {
                    display: -webkit-box;
                    display: -ms-flexbox;
                    display: flex;
                    -webkit-box-align: center;
                    -ms-flex-align: center;
                    align-items: center;
                    -webkit-box-pack: center;
                    -ms-flex-pack: center;
                    justify-content: center;
                    height: 100vh;
                    width: 100%;
                    -webkit-transition: -webkit-transform .5s;
                    transition: -webkit-transform .5s;
                    transition: transform .5s;
                    transition: transform .5s, -webkit-transform .5s;
                }

                .stack-container {
                    position: relative;
                    -webkit-transition: width 1s, height 1s;
                    transition: width 1s, height 1s;
                    width: 420px;
                    height: 210px;
                }

                @media screen and (max-width: 930px) {
                    .stack-container {
                        height: 50vh;
                    }
                }

                .pokeup {
                    -webkit-transition: all .3s ease;
                    transition: all .3s ease;
                }

                .pokeup:hover {
                    -webkit-transform: translateY(-10px);
                    transform: translateY(-10px);
                    -webkit-transition: .3s ease;
                    transition: .3s ease;
                }

                .error {
                    width: 50vw;
                    height: 100vh;
                    text-align: left;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }

                @media screen and (max-width: 930px) {
                    .error {
                        width: 100%;
                        height: 50vh;
                    }
                }

                .error h2 {
                    margin: 0 0 15px 0;
                    padding: 0px;
                    font-size: 30px;
                    letter-spacing: 0px;
                }

                .error-inner {
                    padding: 5vw;
                    display: flex;
                    flex-direction: column;
                    justify-content: center;
                }

                .error-file {
                    border: 1px solid #ddd;
                    padding: 15px;
                    border-radius: 4px;
                    display: block;
                    position: relative;
                }

                .error-file:before {
                    content: '';
                    position: absolute;
                    top: 0;
                    left: 80px;
                    width: 1px;
                    height: 100%;
                    border-right: 1px dashed #ddd;
                }

                .error-file code {
                    display: flex;
                }

                .error-file code strong {
                    flex: 0 0 80px;
                }

                .error-file code:not(:last-child) {
                    margin-bottom: 10px;
                    padding-bottom: 10px;
                    border-bottom: 1px dashed #ddd;
                }

                .error-message span {
                    color: #b11212;
                }

                .button {
                    background: #7789fd;
                    color: #fff;
                    border: 0;
                    padding: 12px 20px;
                    font-size: 16px;
                    font-weight: 600;
                    border-radius: 2px;
                    transition: background 0.3s;
                    cursor: pointer;
                    position: relative;
                    display: inline-block;
                    margin-right: 5px;
                }

                .button:hover {
                    background-color: #5269fe;
                }

                .button.success {
                    background: #50a350;
                    cursor: initial;
                }

                .button.failed {
                    background: #a04646;
                    cursor: initial;
                }

                .button.loading span {
                    visibility: hidden;
                    opacity: 0;
                }

                .button.loading:after {
                    content: "";
                    position: absolute;
                    width: 16px;
                    height: 16px;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    margin: auto;
                    border: 4px solid transparent;
                    border-top-color: #ffffff;
                    border-radius: 50%;
                    animation: button-loading-spinner 1s ease infinite;
                }

                .refresh-btn {
                    display: none;
                }

                @keyframes button-loading-spinner {
                    from {
                        transform: rotate(0turn);
                    }

                    to {
                        transform: rotate(1turn);
                    }
                }

                .perspec {
                    -webkit-perspective: 1000px;
                    perspective: 1000px;
                }

                .writeLine {
                    -webkit-animation: writeLine .4s linear forwards;
                    animation: writeLine .4s linear forwards;
                }

                .explode {
                    -webkit-animation: explode .5s ease-in-out forwards;
                    animation: explode .5s ease-in-out forwards;
                }

                .card {
                    -webkit-animation: tiltcard .5s ease-in-out 1s forwards;
                    animation: tiltcard .5s ease-in-out 1s forwards;
                    position: absolute;
                }

                @-webkit-keyframes tiltcard {
                    0% {
                        -webkit-transform: rotateY(0deg);
                        transform: rotateY(0deg);
                    }

                    100% {
                        -webkit-transform: rotateY(-30deg);
                        transform: rotateY(-30deg);
                    }
                }

                @keyframes tiltcard {
                    0% {
                        -webkit-transform: rotateY(0deg);
                        transform: rotateY(0deg);
                    }

                    100% {
                        -webkit-transform: rotateY(-30deg);
                        transform: rotateY(-30deg);
                    }
                }

                @-webkit-keyframes explode {
                    0% {
                        -webkit-transform: translate(0, 0) scale(1);
                        transform: translate(0, 0) scale(1);
                    }

                    100% {
                        -webkit-transform: translate(var(--spreaddist), var(--vertdist)) scale(var(--scaledist));
                        transform: translate(var(--spreaddist), var(--vertdist)) scale(var(--scaledist));
                    }
                }

                @keyframes explode {
                    0% {
                        -webkit-transform: translate(0, 0) scale(1);
                        transform: translate(0, 0) scale(1);
                    }

                    100% {
                        -webkit-transform: translate(var(--spreaddist), var(--vertdist)) scale(var(--scaledist));
                        transform: translate(var(--spreaddist), var(--vertdist)) scale(var(--scaledist));
                    }
                }

                @-webkit-keyframes writeLine {
                    0% {
                        width: 0;
                    }

                    100% {
                        width: var(--linelength);
                    }
                }

                @keyframes writeLine {
                    0% {
                        width: 0;
                    }

                    100% {
                        width: var(--linelength);
                    }
                }

                @media screen and (max-width: 1000px) {
                    .container {
                        -webkit-transform: scale(.85);
                        transform: scale(.85);
                    }
                }

                @media screen and (max-width: 850px) {
                    .container {
                        -webkit-transform: scale(.75);
                        transform: scale(.75);
                    }
                }

                @media screen and (max-width: 930px) {
                    .container {
                        -ms-flex-wrap: wrap-reverse;
                        flex-wrap: wrap-reverse;
                    }
                }

                @media screen and (max-width: 370px) {
                    .container {
                        -webkit-transform: scale(.6);
                        transform: scale(.6);
                    }
                }
            </style>
            <script lang="js">
                const stackContainer = document.querySelector('.stack-container');
                const cardNodes = document.querySelectorAll('.card-container');
                const perspecNodes = document.querySelectorAll('.perspec');
                const perspec = document.querySelector('.perspec');
                const card = document.querySelector('.card');
                const recoverBtn = document.querySelector('.recover-btn');
                const refreshBtn = document.querySelector('.refresh-btn');

                let counter = stackContainer.children.length;

                const onSuccess = () => {
                    recoverBtn.querySelector('span').innerText = 'File Recovered Successfully!';
                    recoverBtn.classList.add("success");
                    recoverBtn.classList.remove("loading");
                    refreshBtn.style.display = 'inline-block';
                };

                const onFailed = () => {
                    recoverBtn.querySelector('span').innerText = 'Failed Recovering File!';
                    recoverBtn.classList.add("failed");
                    recoverBtn.classList.remove("loading");
                };

                recoverBtn.addEventListener('click', (evt) => {
                    evt.preventDefault();
                    if (recoverBtn.classList.contains("success") || recoverBtn.classList.contains("loading")) {
                        return;
                    }
                    recoverBtn.classList.add("loading");
                    const recoverUrl = '<?php echo add_query_arg('recover', 1, admin_url('admin.php?page=' . self::$slug . '-filemanager'));?>';
                    fetch(recoverUrl)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {

                                cardNodes.forEach(function (elem, index) {
                                    setTimeout(() => {
                                        animateCard(elem);

                                        if (cardNodes.length === index + 1) {

                                            onSuccess();
                                        }

                                    }, 500 * (index * 0.5) + index * 50);
                                });

                            } else {

                                onFailed();
                            }
                        })
                        .catch(() => onFailed());
                });

                refreshBtn.addEventListener('click', (evt) => {
                    evt.preventDefault();

                    if (refreshBtn.classList.contains("loading")) {
                        return;
                    }
                    refreshBtn.classList.add("loading");

                    location.reload();
                });

                function animateCard(elem) {
                    let updown = [800, -800];
                    let randomY = updown[Math.floor(Math.random() * updown.length)];
                    let randomX = Math.floor(Math.random() * 1000) - 1000;
                    elem.style.transform = `translate(${randomX}px, ${randomY}px) rotate(-540deg)`;
                    elem.style.transition = "transform 1s ease, opacity 2s";
                    elem.style.opacity = "0";
                    counter--;
                    if (counter === 0) {
                        stackContainer.style.width = "0";
                        stackContainer.style.height = "0";
                    }
                }

                //function to generate random number
                function randomIntFromInterval(min, max) {
                    return Math.floor(Math.random() * (max - min + 1) + min);
                }

                //after tilt animation, fire the explode animation
                card.addEventListener('animationend', function () {
                    perspecNodes.forEach(function (elem, index) {
                        elem.classList.add('explode');
                    });
                });

                //after explode animation do a bunch of stuff
                perspec.addEventListener('animationend', function (e) {
                    if (e.animationName === 'explode') {
                        cardNodes.forEach(function (elem, index) {

                            //add hover animation class
                            elem.classList.add('pokeup');


                            //generate random number of lines of code between 4 and 10 and add to each card
                            let numLines = randomIntFromInterval(5, 10);

                            //loop through the lines and add them to the DOM
                            for (let index = 0; index < numLines; index++) {
                                let lineLength = randomIntFromInterval(25, 97);
                                var node = document.createElement("li");
                                node.classList.add('node-' + index);
                                elem.querySelector('.code ul').appendChild(node).setAttribute('style', '--linelength: ' + lineLength + '%;');

                                //draw lines of code 1 by 1
                                if (index == 0) {
                                    elem.querySelector('.code ul .node-' + index).classList.add('writeLine');
                                } else {
                                    elem.querySelector('.code ul .node-' + (index - 1)).addEventListener('animationend', function (e) {
                                        elem.querySelector('.code ul .node-' + index).classList.add('writeLine');
                                    });
                                }
                            }
                        });
                    }
                });
            </script>
            <?php
        }
    }

    WPIDE_Error_Handler::boot();
}