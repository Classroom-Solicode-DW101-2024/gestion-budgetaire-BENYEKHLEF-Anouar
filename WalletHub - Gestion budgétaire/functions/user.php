<?php

//
function logIn($email, $password, $connection) {
    $stmt = $connection->prepare("SELECT id, nom, email, password FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        return $user;
    }
    return false;
}

// 
function addUser($user, $connection) {
    $stmt = $connection->prepare("INSERT INTO users (nom, email, password) VALUES (:nom, :email, :password)");
    $stmt->bindParam(':nom', $user['nom'], PDO::PARAM_STR);
    $stmt->bindParam(':email', $user['email'], PDO::PARAM_STR);
    $hashed = password_hash($user['password'], PASSWORD_DEFAULT);
    $stmt->bindParam(':password', $hashed, PDO::PARAM_STR);
    return $stmt->execute();
}


?>
