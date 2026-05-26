<?php
    if(!CODE) die('Bạn không có quyền truy cập vào trang này');

    function query($sql,$data=[],$check=false){
        global $conn;
        try{
            $stmt = $conn->prepare($sql);
            $result = $stmt->execute($data);
            if($check) return $stmt;
            return $result;
        }catch(PDOException $e){
            die("Lỗi truy vấn: " . $e->getMessage());
        }
    }

    
    function insert($table, $data){
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        return query($sql, $data);
        }

    function insertGetId($table, $data){
        global $conn;

        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

        try {
            $stmt = $conn->prepare($sql);
            $stmt->execute($data);
            return (int) $conn->lastInsertId();
        } catch (PDOException $e) {
            die("Lỗi truy vấn: " . $e->getMessage());
        }
    }

    function update($table, $data, $condition, $conditionData){
        $set = [];
        foreach($data as $key=>$value){
            $set[] = "$key = :$key";
        }
        $setClause = implode(", ",$set);
        if(empty($condition)) die("Điều kiện không được để trống");
        $sql = "UPDATE $table SET $setClause WHERE $condition";
        return query($sql,array_merge($data,$conditionData));
    }

    function delete($table, $condition, $conditionData){
        if(empty($condition)) die("Điều kiện không được để trống");
        $sql = "DELETE FROM $table WHERE $condition";
        return query($sql,$conditionData);
    }

    function getOne($sql, $data=[]){
        $stmt = query($sql, $data, true);
        if(is_object($stmt)){
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    function getAll($sql, $data=[]){
        $stmt = query($sql, $data, true);
        if(is_object($stmt)){
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return false;
    }

    function countRows($sql, $data=[]){
        $stmt = query($sql, $data, true);
        if(is_object($stmt)){
            return $stmt->rowCount();
        }
        return false;
    }
