<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// $pubPath = realpath(dirname(__FILE__));
// $appPath = realpath($pubPath . '/../app');
// $libPath = realpath($pubPath . '/../lib');
// $testPath = realpath($pubPath . '/../test');
// $varPath = realpath($pubPath . '/../var');

// set_include_path(implode(PATH_SEPARATOR, array($appPath, $libPath, $testPath, get_include_path())));

define('N2N_STAGE', 'test');

require __DIR__ . '/../vendor/autoload.php';

// TypeLoader::register(true,
// 		require __DIR__ . '/../vendor/composer/autoload_psr4.php',
// 		require __DIR__ . '/../vendor/composer/autoload_classmap.php');

// N2N::initialize($pubPath, $varPath, new FileN2nCache());

// $testSqlFsPath = N2N::getVarStore()->requestFileFsPath('bak', null, null, 'backup.sql', false, false, false);

// $sql = IoUtils::getContents($testSqlFsPath);

// $sql = preg_replace('/^(INSERT|VALUES|\().*/m', '', $sql);
// $sql = preg_replace('/^ALTER TABLE .* ADD (INDEX|UNIQUE|FULLTEXT).*/m', '', $sql);
// $sql = preg_replace('/ENGINE=InnoDB DEFAULT CHARSET=utf8[^\W]* COLLATE [^;]+/', '', $sql);
// $sql = preg_replace('/\\,(\\s)*PRIMARY KEY.*/m', '', $sql);
// $sql = preg_replace('/ENUM\([^\)]+\)/', 'VARCHAR(255)', $sql);
// $sql = preg_replace('/INT (UNSIGNED )?NOT NULL AUTO_INCREMENT/', 'INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
// $sql = preg_replace("/[\r\n]+/", "\n", $sql);
// $sql = str_replace('UNSIGNED ', '', $sql);
// file_put_contents('huii.sql', $sql);

// N2N::getPdoPool()->getPdo()->exec($sql);
