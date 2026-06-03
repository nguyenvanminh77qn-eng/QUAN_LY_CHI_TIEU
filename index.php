<?php
    require_once "config.php";
    require_once "includes/Phpmailer/Exception.php";
    require_once "includes/Phpmailer/PHPMailer.php";
    require_once "includes/Phpmailer/SMTP.php";
    require_once "includes/functions.php";
    require_once "includes/session.php";
    require_once "includes/connect.php";
    require_once "includes/database.php";
    require_once "includes/transaction_policy.php";
    require_once "includes/transaction_helpers.php";
require_once "includes/wallet_helper.php";

    // Bảo mật session
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    // Kiểm tra trạng thái tài khoản toàn cục
    $loginToken = getSession('loginToken');
    if (!empty($loginToken)) {
        $userCheck = getOne("SELECT user.status, user.id FROM user JOIN logintoken ON user.id = logintoken.user_id WHERE logintoken.loginToken = :token", ['token' => $loginToken]);
        if (!$userCheck) {
            // Token không tồn tại hoặc đã bị xóa (Admin xóa)
            deleteSession('loginToken');
            deleteSession('role');
            deleteSession('username');
            deleteSession('id');
            setMessage("Tài khoản của bạn đã bị khóa bởi quản trị viên", "error");
            redirect("?template=auth&action=login.view");
        }
    }
    
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

    if($_SERVER['REQUEST_METHOD'] == "POST"){
        $pathModule = "modules/{$template}/{$action}.php";
        if(file_exists($pathModule)){
            require_once _WEB_PATH.$pathModule;
        }else{
            require_once _WEB_PATH.'templates/error/404.php';
        }
    }else{
        $pathTemplate = "templates/{$template}/{$action}.php";
        if(file_exists($pathTemplate)){
            require_once _WEB_PATH.$pathTemplate;
        }else{
            require_once _WEB_PATH.'templates/error/404.php';
        }
    }
    
