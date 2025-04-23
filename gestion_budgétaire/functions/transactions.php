<?php
// Adds a new transaction
function addTransaction($transaction, $connection)
{
    $stmt = $connection->prepare("INSERT INTO transactions (user_id, category_id, montant, description, date_transaction) 
    VALUES (:user_id, :category_id, :montant, :description, :date_transaction)");
    $stmt->bindParam(':user_id', $transaction['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':category_id', $transaction['category_id'], PDO::PARAM_INT);
    $stmt->bindParam(':montant', $transaction['montant'], PDO::PARAM_STR);
    $stmt->bindParam(':description', $transaction['description'], PDO::PARAM_STR);
    $stmt->bindParam(':date_transaction', $transaction['date_transaction'], PDO::PARAM_STR);
    return $stmt->execute();
}

// Deletes a transaction by id
function deleteTransaction($idTransaction, $connection)
{
    $stmt = $connection->prepare("DELETE FROM transactions WHERE id = :id");
    $stmt->bindParam(':id', $idTransaction, PDO::PARAM_INT);
    return $stmt->execute();
}

// Edits a transaction by id
function editTransaction($idTransaction, $newTransaction, $connection)
{
    $stmt = $connection->prepare("UPDATE transactions SET category_id = :category_id, montant = :montant, description = :description, date_transaction = :date_transaction 
    WHERE id = :id");
    $stmt->bindParam(':category_id', $newTransaction['category_id'], PDO::PARAM_INT);
    $stmt->bindParam(':montant', $newTransaction['montant'], PDO::PARAM_STR);
    $stmt->bindParam(':description', $newTransaction['description'], PDO::PARAM_STR);
    $stmt->bindParam(':date_transaction', $newTransaction['date_transaction'], PDO::PARAM_STR);
    $stmt->bindParam(':id', $idTransaction, PDO::PARAM_INT);
    return $stmt->execute();
}

// Lists all transactions for a user (ordered by date descending)
function listTransactions($connection, $userId)
{
    $stmt = $connection->prepare("SELECT t.*, c.nom as category_name, c.type 
    FROM transactions t JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = :user_id 
    ORDER BY date_transaction DESC");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Lists transactions for a specific month and year
function listTransactionsByMonth($connection, $userId, $year, $month)
{
    $stmt = $connection->prepare("SELECT t.*, c.nom as category_name, c.type 
    FROM transactions t JOIN categories c ON t.category_id = c.id 
    WHERE t.user_id = :user_id AND YEAR(date_transaction) = :year 
    AND MONTH(date_transaction) = :month ORDER BY date_transaction DESC");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':year', $year, PDO::PARAM_INT);
    $stmt->bindParam(':month', $month, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getTransactionYears($connection, $userId) {
    $stmt = $connection->prepare("SELECT DISTINCT YEAR(date_transaction) as year FROM transactions WHERE user_id = :user_id ORDER BY year DESC");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
