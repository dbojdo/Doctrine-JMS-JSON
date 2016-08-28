<?php
$loader = include __DIR__ . '/../vendor/autoload.php';
\Doctrine\Common\Annotations\AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
    "JMS\\Serializer\\Annotation",
    __DIR__ . '/../vendor/jms/serializer/src/JMS/Serializer/Annotation'
);
