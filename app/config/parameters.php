<?php

$container->setParameter('database_driver', 'pdo_mysql');
$container->setParameter('database_host', 'localhost');
$container->setParameter('database_port', null);
$container->setParameter('database_name', 'friendscore');
$container->setParameter('database_name_test', 'friendscore_test');
$container->setParameter('database_user', 'root');
$container->setParameter('database_password', null);
$container->setParameter('database_path', null);

$container->setParameter('mailer_transport', 'smtp');
$container->setParameter('mailer_host', 'localhost');
$container->setParameter('mailer_user', null);
$container->setParameter('mailer_password', null);
$container->setParameter('locale', 'en');
$container->setParameter('secret', 'd374ac3307aa6c27dfbec09d196945c4');

$container->setParameter('elasticsearch_host', 'localhost');
$container->setParameter('elasticsearch_port', '9200');
$container->setParameter('elasticsearch_path', '');
