<?php

// Define CODE first (needed by env-loader.php)
const CODE = true;

// Load environment variables
require_once __DIR__ . "/includes/env-loader.php";

const TEMPLATE = "home";
const ACTION = "welcome";


define('_WEB_HOST', 'http://'.$_SERVER['HTTP_HOST'].'/QUAN_LY_CHI_TIEU');

define('_WEB_ROOT', _WEB_HOST.'/');

define('_WEB_PATH', __DIR__.'/');

define('WEB_PATH_TEMPLATE', _WEB_PATH.'templates/');

define('_ASSETS', _WEB_ROOT.'assets/');

define('_CSS', _ASSETS.'css/');

define('_JS', _ASSETS.'js/');

define('_IMG', _ASSETS.'images/');