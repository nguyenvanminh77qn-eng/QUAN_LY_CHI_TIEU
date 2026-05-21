<?php
if(!CODE) die('Bạn không có quyền truy cập vào trang này');


if (isset($_POST['filter-btn'])) {
    $filterALl = filter();

    $oldInputs = [];
    $oldInputs['transaction_date'] = $filterALl['transaction_date'];
    $oldInputs['type'] = $filterALl['type'];
    $oldInputs['category_id'] = $filterALl['category_id'];
    $oldInputs['description'] = $filterALl['description'];
    $oldInputs['price_min'] = $filterALl['price_min'] ?? '';
    $oldInputs['price_max'] = $filterALl['price_max'] ?? '';

    $where = "WHERE transaction.user_id = :user_id AND transaction.is_archived = 0";
    $params = ['user_id' => $filterALl['id']];

    if (!empty($filterALl['transaction_date'])) {
        $where .= " AND transaction_date >= :transaction_date";
        $params['transaction_date'] = $filterALl['transaction_date'];
    }
    if (!empty($filterALl['type'])) {
        $where .= " AND type = :type";
        $params['type'] = $filterALl['type'];
    }
    if (!empty($filterALl['category_id'])) {
        $where .= " AND category_id = :category_id";
        $params['category_id'] = $filterALl['category_id'];
    }
    if (!empty($filterALl['description'])) {
        $where .= " AND LOWER(description) LIKE LOWER(:description)";
        $params['description'] = '%' . trim($filterALl['description']) . '%';
    }
    $priceMin = trim($filterALl['price_min'] ?? '');
    $priceMax = trim($filterALl['price_max'] ?? '');
    if ($priceMin !== '' && is_numeric($priceMin) && (float)$priceMin >= 0) {
        $where .= " AND price >= :price_min";
        $params['price_min'] = (float)$priceMin;
    }
    if ($priceMax !== '' && is_numeric($priceMax) && (float)$priceMax >= 0) {
        $where .= " AND price <= :price_max";
        $params['price_max'] = (float)$priceMax;
    }

    $sqlCount = "SELECT COUNT(*) as total FROM transaction JOIN category ON category.id = transaction.category_id $where";
    $total = getOne($sqlCount, $params)['total'];

    setSession("filter_where", $where);
    setSession("filter_params", $params);
    setSession("filter_oldInputs", $oldInputs);
    setSession("filter_total", $total);

    redirect("?template=user&action=filter");
}

if(isset($_POST['filter-reset-btn'])){
    deleteSession("filter_where");
    deleteSession("filter_params");
    deleteSession("filter_oldInputs");
    deleteSession("filter_total");
    redirect("?template=user&action=filter");
}
