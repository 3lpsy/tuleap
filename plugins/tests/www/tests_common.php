<?php

ini_set('display_errors', 'on');
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
date_default_timezone_set('Europe/Paris');

if (version_compare(PHP_VERSION, '5.3.0', '>=')) { 
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    error_reporting(E_ALL);
}

// Base dir:
$basedir      = realpath(dirname(__FILE__).'/../../..');
$src_path     = $basedir.'/src';
$include_path = $basedir.'/src/www/include';

ini_set('include_path', ini_get('include_path').':'.$src_path.':'.$include_path);

$local_inc = getenv('TULEAP_LOCAL_INC') ? getenv('TULEAP_LOCAL_INC') : getenv('CODENDI_LOCAL_INC');
if ( ! $local_inc ) {
    if (is_file('/etc/tuleap/conf/local.inc')) {
        $local_inc = '/etc/tuleap/conf/local.inc';
    } else {
        $local_inc = '/etc/codendi/conf/local.inc';
    }
}
require($local_inc);

// Fix path if needed
if (isset($GLOBALS['jpgraph_dir'])) {
    ini_set('include_path', ini_get('include_path').PATH_SEPARATOR.$GLOBALS['jpgraph_dir']);
}

require_once('common/autoload_libs.php');
require_once('common/autoload.php');

require_once dirname(__FILE__).'/../include/simpletest/unit_tester.php';
require_once dirname(__FILE__).'/../include/simpletest/mock_objects.php';
require_once dirname(__FILE__).'/../include/simpletest/web_tester.php';
require_once dirname(__FILE__).'/../include/simpletest/expectation.php';
require_once dirname(__FILE__).'/../include/simpletest/collector.php';

require_once dirname(__FILE__).'/../../../tests/lib/autoload.php';
require_once dirname(__FILE__).'/../../../tests/lib/constants.php';

require_once __DIR__.'/../../../src/www/include/utils.php';
require_once __DIR__.'/../../../src/www/project/admin/permissions.php';
