<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->add('SocketIO\Emitter', __DIR__ . '/../vendor/rase/socket.io-emitter/src/');
$classLoader = new \Doctrine\Common\ClassLoader(
    'DoctrineExtensions', __DIR__."/../src/Api/ApiBundle"
    );
$classLoader->register();

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

return $loader;
