<?php
if (!CODE) {
    die('Ban khong co quyen truy cap vao trang nay');
}

/**
 * Lấy hoặc tạo danh mục "Khác" dùng cho giao dịch điều chỉnh chốt sổ.
 */
function getOrCreateOtherCategoryId(): int {
    $otherCategory = getOne("SELECT id FROM category WHERE LOWER(name) = 'khác' OR LOWER(name) = 'khac' LIMIT 1");
    if ($otherCategory) {
        return (int) $otherCategory['id'];
    }

    $insertId = insertGetId('category', [
        'name' => 'Khác',
        'icon' => '📝',
    ]);

    return (int) $insertId;
}

/**
 * Tạo giao dịch điều chỉnh số dư sau khi chốt sổ.
 * Nếu differenceAmount > 0 → thu nhập (thực tế cao hơn hệ thống).
 * Nếu differenceAmount < 0 → chi tiêu (thực tế thấp hơn hệ thống).
 */
function createBalanceAdjustmentTransaction(int $userId, float $differenceAmount, string $transactionDate, string $note = ''): ?int {
    if (abs($differenceAmount) < 1) {
        return null;
    }

    $categoryId = getOrCreateOtherCategoryId();
    $type       = $differenceAmount >= 0 ? 'income' : 'expense';
    $amount     = abs($differenceAmount);
    $noteSuffix = trim($note) !== '' ? ' - ' . trim($note) : '';

    return insertGetId('transaction', [
        'user_id'          => $userId,
        'category_id'      => $categoryId,
        'price'            => $amount,
        'description'      => 'Điều chỉnh số dư sau chốt sổ' . $noteSuffix,
        'transaction_date' => $transactionDate,
        'type'             => $type,
        'create_at'        => date('Y-m-d H:i:s'),
        'source_type'      => 'adjustment',
        'evidence_text'    => trim($note) !== '' ? trim($note) : null,
    ]);
}
