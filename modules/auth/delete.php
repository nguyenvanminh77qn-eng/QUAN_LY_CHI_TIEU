<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');

if(isset($_POST['btn-delete'])){
    $filterAll = filter();

    if(empty($filterAll['ids[]'])){
        setMessage("Vui lòng chọn giao dịch để xóa");
        redirect("?template=user&action=filter");
    }

    $deleteQuery = delete("transaction","user_id = :id AND id IN :ids",['id'=>$filterAll['id'],'ids'=>$filterAll['ids[]']]);
    if($deleteQuery){
        setMessage("Xóa thành công");
    }else{
        setMessage("Lỗi hệ thống, vui lòng thử lại sau");
    }
    redirecṭ̣("?template=user&action=filter");
    

}