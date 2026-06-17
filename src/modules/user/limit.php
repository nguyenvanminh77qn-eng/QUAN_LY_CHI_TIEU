<?php
if (!CODE) die('Bạn không có quyền truy cập vào trang này');

$userId = getSession('id');
if (!$userId) {
    setMessage("Bạn phải đăng nhập", "error");
    redirect("?template=auth&action=login.view");
}
if (getSession('role') !== 'user') {
    setMessage("Bạn không có quyền truy cập trang này", "error");
    redirect("?template=admin&action=dashboard");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_limit'])) {
    $filterAll = filter();

    if (!empty($filterAll['amounts']) && is_array($filterAll['amounts'])) {
        foreach ($filterAll['amounts'] as $categoryId => $amount) {
            $categoryId = (int)$categoryId;
            $amount = str_replace('.', '', $amount);
            $amount = str_replace(',', '.', $amount);
            $amount = max(0, (float)$amount);

            if ($categoryId <= 0) continue;

            $existing = getOne(
                "SELECT id FROM category_limit WHERE user_id = :uid AND category_id = :cid",
                ['uid' => $userId, 'cid' => $categoryId]
            );

            if ($amount > 0) {
                if ($existing) {
                    update('category_limit', ['max_amount' => $amount, 'updated_at' => date('Y-m-d H:i:s')], "id = :id", ['id' => $existing['id']]);
                } else {
                    insert('category_limit', [
                        'user_id' => $userId,
                        'category_id' => $categoryId,
                        'max_amount' => $amount,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
            } else {
                if ($existing) {
                    delete('category_limit', "id = :id", ['id' => $existing['id']]);
                }
            }
        }
        setMessage("Đã lưu hạn mức danh mục", "success");
    }

    $redirectUrl = !empty($filterAll['redirect']) ? $filterAll['redirect'] : '?template=user&action=limit';
    redirect($redirectUrl);
}
