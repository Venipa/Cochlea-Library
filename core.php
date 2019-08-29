<?php

/**
 * Author: Venipa <admin@venipa.net>
 * Type: MyBB Extension Plugin Library
 * Description: This is a Plugin Library used by other Plugins, note: this plugin does not need to be registered
 * Disclaimer: This Plugin can be used by everyone, you just need to add me to your credit page or just mention the use of this Script
 * Usage:
 *  - `composer install` - installs dependencies
 *  - `require "../chochlea-library/core.php"` - make sure to adjust the path to this file
 * 
 * License: Gnu GPL3 <https://www.gnu.org/licenses/gpl-3.0.de.html>
 * Website: https://venipa.net
 */

 if (!defined('IN_MYBB')) {
    die('Cochlea Library cannot be accessed without a MyBB Context.');
}

require_once(__DIR__ . '/vendor/autoload.php');
require_once(__DIR__ . '/src/bootstrap.php');