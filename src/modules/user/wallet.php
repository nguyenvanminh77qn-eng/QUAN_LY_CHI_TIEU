<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');
if (getSession('role') !== 'user') { setMessage("Bạn không có quyền","error"); redirect("?template=admin&action=dashboard"); }

$userId = getSession('id');
$filterAll = filter();
$isAjax = isset($_POST['ajax']) && $_POST['ajax'] == 1;

// ── Lưu (thêm / sửa) ──
if (isset($_POST['save_wallet'])) {
    $name = trim($filterAll['name'] ?? '');
    $icon = trim($filterAll['icon'] ?? '💰');
    $type = $filterAll['type'] ?? 'daily';
    $id = (int)($filterAll['id'] ?? 0);

    if (empty($name)) {
        if ($isAjax) jsonResponse(false, 'Tên ví không được để trống.');
        setMessage('Tên ví không được để trống.','error'); redirect('?template=user&action=wallet&noanim=1');
    }
    if (!in_array($type, ['daily','ewallet','target'], true)) $type = 'daily';

    if ($id > 0) {
        $existing = getOne("SELECT id, is_default FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$id, 'uid'=>$userId]);
        if (!$existing) {
            if ($isAjax) jsonResponse(false, 'Ví không tồn tại.');
            setMessage('Ví không tồn tại.','error'); redirect('?template=user&action=wallet&noanim=1');
        }
        update('wallet', ['name'=>$name, 'icon'=>$icon, 'type'=>$type], "id = :id AND user_id = :uid", ['id'=>$id, 'uid'=>$userId]);
        if ($isAjax) {
            $allWallets = getWallets($userId);
            $gt = 0;
            foreach ($allWallets as $w) $gt += getWalletBalance($w['id'], $userId);
            jsonResponse(true, 'Đã cập nhật ví.', ['wallet'=>['id'=>$id,'name'=>$name,'icon'=>$icon,'type'=>$type,'balance'=>getWalletBalance($id,$userId)],'grand_total'=>$gt]);
        }
        setMessage('Đã cập nhật ví.','success');
    } else {
        $newId = insertGetId('wallet', ['user_id'=>$userId, 'name'=>$name, 'icon'=>$icon, 'type'=>$type, 'created_at'=>date('Y-m-d H:i:s')]);
        if (!$newId) {
            if ($isAjax) jsonResponse(false, 'Không thể tạo ví. Vui lòng thử lại.');
            setMessage('Không thể tạo ví.','error'); redirect('?template=user&action=wallet&noanim=1');
        }
        if ($isAjax) {
            $allWallets = getWallets($userId);
            $gt = 0;
            foreach ($allWallets as $w) $gt += getWalletBalance($w['id'], $userId);
            jsonResponse(true, 'Đã thêm ví mới.', ['wallet'=>['id'=>$newId,'name'=>$name,'icon'=>$icon,'type'=>$type,'balance'=>0],'grand_total'=>$gt]);
        }
        setMessage('Đã thêm ví mới.','success');
    }
    if (!$isAjax) redirect('?template=user&action=wallet&noanim=1');
}

// ── Xóa ──
if (isset($_POST['delete_wallet'])) {
    $id = (int)($filterAll['id'] ?? 0);
    $wallet = getOne("SELECT id, is_default FROM wallet WHERE id = :id AND user_id = :uid", ['id'=>$id, 'uid'=>$userId]);
    if (!$wallet) {
        if ($isAjax) jsonResponse(false, 'Ví không tồn tại.');
        setMessage('Ví không tồn tại.','error'); redirect('?template=user&action=wallet&noanim=1');
    }
    if ($wallet['is_default']) {
        if ($isAjax) jsonResponse(false, 'Không thể xóa ví mặc định.');
        setMessage('Không thể xóa ví mặc định.','error'); redirect('?template=user&action=wallet&noanim=1');
    }

    $txCount = getOne("SELECT COUNT(*) as cnt FROM transaction WHERE wallet_id = :wid", ['wid'=>$id]);
    if ($txCount && $txCount['cnt'] > 0) {
        if ($isAjax) jsonResponse(false, 'Không thể xóa ví đã có giao dịch.');
        setMessage('Không thể xóa ví đã có giao dịch.','error'); redirect('?template=user&action=wallet&noanim=1');
    }

    delete('wallet', "id = :id AND user_id = :uid", ['id'=>$id, 'uid'=>$userId]);
    if ($isAjax) {
        $allWallets = getWallets($userId);
        $gt = 0;
        foreach ($allWallets as $w) $gt += getWalletBalance($w['id'], $userId);
        jsonResponse(true, 'Đã xóa ví.', ['deleted_id'=>$id,'grand_total'=>$gt]);
    }
    setMessage('Đã xóa ví.','success');
    if (!$isAjax) redirect('?template=user&action=wallet&noanim=1');
}
