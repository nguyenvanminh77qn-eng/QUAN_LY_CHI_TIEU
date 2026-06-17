<?php
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../../vendor/phpmailer/Exception.php';
    require_once __DIR__ . '/../../vendor/phpmailer/PHPMailer.php';
    require_once __DIR__ . '/../../vendor/phpmailer/SMTP.php';
    require_once __DIR__ . '/../app/Core/functions.php';
    require_once __DIR__ . '/../app/Core/session.php';
    require_once __DIR__ . '/../app/Core/connect.php';
    require_once __DIR__ . '/../app/Core/database.php';
    require_once __DIR__ . '/../app/Helpers/transaction_policy.php';
    require_once __DIR__ . '/../app/Helpers/transaction_helpers.php';
    require_once __DIR__ . '/../app/Helpers/wallet_helper.php';
    require_once __DIR__ . '/../app/Helpers/feedback_helper.php';

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    $loginToken = getSession('loginToken');
    if (!empty($loginToken)) {
        $userCheck = getOne("SELECT user.status, user.id FROM user JOIN logintoken ON user.id = logintoken.user_id WHERE logintoken.loginToken = :token", ['token' => $loginToken]);
        if (!$userCheck) {
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
        $pathModule = "src/modules/{$template}/{$action}.php";
        if(file_exists($pathModule)){
            require_once _WEB_PATH.$pathModule;
        }else{
            require_once _WEB_PATH.'src/views/error/404.php';
        }
    }else{
        $pathTemplate = "src/views/{$template}/{$action}.php";
        if(file_exists($pathTemplate)){
            require_once _WEB_PATH.$pathTemplate;
        }else{
            require_once _WEB_PATH.'src/views/error/404.php';
        }
    }
