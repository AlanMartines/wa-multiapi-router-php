<?php
// Time zone
setlocale(LC_TIME, 'pt_BR.utf8');
date_default_timezone_set('America/Sao_Paulo');
//
/** pasta absoluta do sistema **/
if (!defined('ABSPAST'))
	define('ABSPAST', basename(__DIR__));
//
/** caminho absoluto para a pasta do sistema **/
if (!defined('ABSPATH'))
	define('ABSPATH', dirname(__FILE__) . '/');
//
//API_TOKEN
if (!defined('ADMINTOKEN'))
	define('ADMINTOKEN', 'AnZ0Ie0IcvjPAaEGeTw4SpTMMLlhyOr0K1aLcORWR0zwU6nGp');
//
if (!defined('APIURLBND'))
	define('APIURLBND', 'https://apiwasrv.connectzap.com.br');
//
/** caminho do arquivo de banco de dados **/
if (!defined('DBAPI'))
	define('DBAPI', ABSPATH . 'inc/conexao.php');
//