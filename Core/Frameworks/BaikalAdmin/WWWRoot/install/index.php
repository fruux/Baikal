<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Jérôme Schneider <mail@jeromeschneider.fr>
*  All rights reserved
*
*  http://sabre.io/baikal
*
*  This script is part of the Baïkal Server project. The Baïkal
*  Server project is free software; you can redistribute it
*  and/or modify it under the terms of the GNU General Public
*  License as published by the Free Software Foundation; either
*  version 2 of the License, or (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

ini_set("session.cookie_httponly", 1);
ini_set("log_errors", 1);
$maxtime = ini_get('max_execution_time');
if ($maxtime != 0 && $maxtime < 3600) {
    ini_set('max_execution_time', 3600); // 1 hour
}
ini_set('ignore_user_abort', true);
error_reporting(E_ALL);

define("BAIKAL_CONTEXT", true);
define("BAIKAL_CONTEXT_INSTALL", true);
define("PROJECT_CONTEXT_BASEURI", "/admin/install/");

if (file_exists(dirname(dirname(getcwd())) . "/Core")) {
    # Flat FTP mode
    define("PROJECT_PATH_ROOT", dirname(dirname(getcwd())) . "/");    #../../
} else {
    # Dedicated server mode
    define("PROJECT_PATH_ROOT", dirname(dirname(dirname(getcwd()))) . "/");    # ../../../
}

if (!file_exists(PROJECT_PATH_ROOT . 'vendor/')) {
    die('<h1>Incomplete installation</h1><p>Ba&iuml;kal dependencies have not been installed. Please, execute "<strong>composer install</strong>" in the folder where you installed Ba&iuml;kal.');
}

require PROJECT_PATH_ROOT . "vendor/autoload.php";

# Bootstraping Flake
\Flake\Framework::bootstrap();

# Bootstrap BaikalAdmin
\BaikalAdmin\Framework::bootstrap();

# Create and setup a page object
$oPage = new \Flake\Controller\Page(BAIKALADMIN_PATH_TEMPLATES . "Page/index.html");
$oPage->injectHTTPHeaders();
$oPage->setTitle("Baïkal Maintainance");
$oPage->setBaseUrl(PROJECT_URI);

$oPage->zone("navbar")->addBlock(new \BaikalAdmin\Controller\Navigation\Topbar\Install());

if (!defined("BAIKAL_CONFIGURED_VERSION")) {
    # we have to upgrade Baïkal (existing installation)
    $oPage->zone("Payload")->addBlock(new \BaikalAdmin\Controller\Install\Initialize());

} elseif (!defined("BAIKAL_ADMIN_PASSWORDHASH")) {
    # we have to set an admin password
    $oPage->zone("Payload")->addBlock(new \BaikalAdmin\Controller\Install\Initialize());
} else {
    if (BAIKAL_CONFIGURED_VERSION !== BAIKAL_VERSION) {
        # we have to upgrade Baïkal
        $oPage->zone("Payload")->addBlock(new \BaikalAdmin\Controller\Install\VersionUpgrade());
    } elseif (!file_exists(PROJECT_PATH_SPECIFIC . '/INSTALL_DISABLED')) {
        $oPage->zone("Payload")->addBlock(new \BaikalAdmin\Controller\Install\Database());
    } else {
        echo "Installation was already completed. Please head to the admin interface to modify any settings.\n";
        die();
    }
}

# Render the page
echo $oPage->render();
