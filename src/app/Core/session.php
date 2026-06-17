<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    function getSession($key){
        return !empty($_SESSION[$key]) ? $_SESSION[$key] : null;
    }

    function setSession($key, $value){
        $_SESSION[$key] = $value;
    }

    function deleteSession($key){
        if(isset($_SESSION[$key])){
            unset($_SESSION[$key]);
            return true;
        }
        return false;
    }

    function getFlashData($key){
        $value = getSession($key);
        deleteSession($key);
        return $value;
    }

    function setFlashData($key, $value){
        setSession($key, $value);
    }
?>