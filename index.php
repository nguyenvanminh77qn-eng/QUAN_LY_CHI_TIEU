
<?php
    require_once "config.php";
    require_once "includes/functions.php";
    
    $template = TEMPLATE;
    $action = ACTION;

    if(isset($_GET['template'])){
        if(is_string($_GET['template']) && !empty($_GET['template'])){
            $template = $_GET['template'];
        }
    }

    if(isset($_GET['action'])){
        if(is_string($_GET['action']) && !empty($_GET['action'])){
            $action = $_GET['action'];
        }
    }

    $pathTemplate = "templates/{$template}/{$action}.php";
    if(file_exists($pathTemplate)){
        require_once _WEB_PATH.$pathTemplate;
    }else{
        require_once _WEB_PATH.'templates/error/404.php';
    }