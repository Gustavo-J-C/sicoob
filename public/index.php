<?php
require_once __DIR__ . '/../src/controllers/TransactionsController.php';

// Configurações da API SICOOB
$apiUrl = 'https://sandbox.sicoob.com.br/sicoob/sandbox/pix/api/v2/pix';
$accessToken = 'Bearer 1301865f-c6bc-38f3-9f49-666dbcfc59c3';
$clientId = '9b5e603e428cc477a2841e2683c92d21';

try {
    $controller = new TransactionsController($apiUrl, $accessToken, $clientId);
    $controller->fetchAndSaveTransactions();
    echo "Transactions fetched and saved successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
