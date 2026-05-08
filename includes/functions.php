
<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

      function layout($view, $data = []) {
        $path = WEB_PATH_TEMPLATE."layout/".$view.".php";

        if(!file_exists($path)){
            die("❌ Không tồn tại: " . $path);
        }

        extract($data);
        require_once $path;
}

    function isGet(){
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    function isPost(){
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    function filter(){
        $filter = [];
        if(isGet()){
            if(!empty($_GET)){
                foreach($_GET as $key => $value){
                   $key = strip_tags($key);
                   if(is_array($value)){
                       $filter[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
                   } else {
                       $filter[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                   }
                }
            }
        }

        if(isPost()){
            if(!empty($_POST)){
                foreach($_POST as $key => $value){
                   $key = strip_tags($key);
                   if(is_array($value)){
                       $filter[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_REQUIRE_ARRAY);
                   } else {
                       $filter[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
                   }
                }
            }
        }

        return $filter;
    }

    function isEmail($email){
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    function isNumberInt($number){
        return filter_var($number, FILTER_VALIDATE_INT);
    }

    function isNumberFloat($number){
        return filter_var($number, FILTER_VALIDATE_FLOAT);
    }

    function isPhone($phone){
        $checkZero = false;
        if($phone[0] === '0'){
            $checkZero = true;
            $phone = substr($phone, 1);
        }
        $isNumber = false;
        if(isNumberInt($phone) && strlen($phone) == 9){
            $isNumber = true;
        }
        return $checkZero && $isNumber;
    }

    function setMessage($message, $type = "success"){
        setFlashData("message",$message);
        setFlashData("message_type",$type);
    }

    function showMessage($message, $type = "success"){
    return "<div class='alert alert-$type'>$message</div>";}

    function form_error($errors, $field){
        if(!empty($errors[$field])){
        return "<span class='form-error'>" . reset($errors[$field]) . "</span>";
    }}

    function redirect($url){
        header("Location: $url");
        exit();
    }

    function old($oldData,$field){
        if(!empty($oldData[$field])){
            return $oldData[$field];
        }
        return "";
    }

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\SMTP;
    use PHPMailer\PHPMailer\Exception;


 function sendMail($to, $subject, $content){
    // Check if SMTP is configured
    if (empty($_ENV['SMTP_HOST']) || empty($_ENV['SMTP_USER']) || empty($_ENV['SMTP_PASS'])) {
        error_log("SMTP is not configured. Please set SMTP_HOST, SMTP_USER, and SMTP_PASS in .env file");
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        // Get SMTP configuration from environment variables
        $smtpHost = $_ENV['SMTP_HOST'];
        $smtpPort = $_ENV['SMTP_PORT'] ?? 587;
        $smtpUser = $_ENV['SMTP_USER'];
        $smtpPass = $_ENV['SMTP_PASS'];
        $appName = $_ENV['APP_NAME'] ?? 'Quản Lý Chi Tiêu';
        
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = $smtpHost;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtpUser;
        $mail->Password   = $smtpPass;
        
        // Determine encryption based on port
        if ($smtpPort == 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        
        $mail->Port       = $smtpPort;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        // Set sender
        $mail->setFrom($smtpUser, $appName);
        $mail->addAddress($to);
        
        // Set content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $content;

        $sent = $mail->send();
        return $sent;
    } catch (Exception $e) {
        error_log("Email send error: {$mail->ErrorInfo}");
        return false;
    }
    }

    function getTotalSum($id, $type) {
        $sql = "SELECT SUM(price) as total FROM transaction WHERE type = :type AND user_id = :id";
        $result = getOne($sql, ["id" => $id, "type" => $type]);
        return ($result && $result['total']) ? $result['total'] : 0;
    }

    function getPagination($totalRows, $limit, $currentPage){
        $totalPages = ceil($totalRows / $limit);
        if($totalPages < 1) $totalPages = 1;
        if($currentPage < 1) $currentPage = 1;
        if($currentPage > $totalPages) $currentPage = $totalPages;
        
        $offset = ($currentPage - 1) * $limit;
        if($offset < 0) $offset = 0;

        return [
            'offset' => $offset,
            'totalPages' => $totalPages,
            'currentPage' => $currentPage
        ];
    }

    function renderPagination($totalPages, $currentPage, $urlPrefix){
        if($totalPages <= 1) return "";
        
        $html = '<div class="pagination-container">';
        $html .= '<ul class="pagination">';
        
        // Prev button
        if($currentPage > 1){
            $html .= '<li><a href="'.$urlPrefix.'&page='.($currentPage - 1).'">❮</a></li>';
        }
        
        for($i = 1; $i <= $totalPages; $i++){
            $active = ($i == $currentPage) ? 'active' : '';
            $html .= '<li class="'.$active.'"><a href="'.$urlPrefix.'&page='.$i.'">'.$i.'</a></li>';
        }
        
        // Next button
        if($currentPage < $totalPages){
            $html .= '<li><a href="'.$urlPrefix.'&page='.($currentPage + 1).'">❯</a></li>';
        }
        
        $html .= '</ul>';
        $html .= '</div>';
        
        return $html;
    }