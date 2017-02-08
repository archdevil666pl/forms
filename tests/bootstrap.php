<?php
/**
 * @author Slawomir Zytko <slawomir.zytko@gmail.com>
 * @copyright Amsterdam Standard Sp. Z o.o.
 * Date: 4/24/14
 * Time: 1:24 PM
 */

//Test Suite bootstrap
include __DIR__ . "/../vendor/autoload.php";

define('TESTS_ROOT_DIR', dirname(__FILE__));

$configArray = require_once dirname(__FILE__) . '/fixtures/app/config/config.php';

$config = new \Phalcon\Config($configArray);
$di = new Phalcon\DI\FactoryDefault();

$di->set('config', $config);

$di->set('mongo', function() use ($config) {
    $mongo = new \MongoClient();
    return $mongo->selectDb($config->mongo->dbname);
}, true);

$di->set('collectionManager', function() {
    return new \Phalcon\Mvc\Collection\Manager();
});

$di->set('filter', '\Vegas\Filter', true);

$view = new \Phalcon\Mvc\View();
$view->registerEngines(array(
    '.volt' => function ($view, $di) {
            $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
            $volt->setOptions(array(
                'compiledPath' => TESTS_ROOT_DIR.'/fixtures/cache/',
                'compiledSeparator' => '_'
            ));

            return $volt;
        },
    '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
));

$di->set('view', $view);

\Phalcon\DI::setDefault($di);