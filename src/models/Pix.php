<?php

class Pix
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function saveTransaction($transaction)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO transactions (transaction_id, value, payer_name, payer_cpf_cnpj, transaction_date, raw_response)
            VALUES (:transaction_id, :value, :payer_name, :payer_cpf_cnpj, :transaction_date, :raw_response)
        ");

        $stmt->execute([
            'transaction_id' => $transaction['transaction_id'],
            'value' => $transaction['value'],
            'payer_name' => $transaction['payer_name'],
            'payer_cpf_cnpj' => $transaction['payer_cpf_cnpj'],
            'transaction_date' => $transaction['transaction_date'],
            'raw_response' => $transaction['raw_response']
        ]);
    }

    public function getTransactions()
    {
        $stmt = $this->pdo->query("SELECT * FROM transactions ORDER BY transaction_date DESC LIMIT 100");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchTransactions($startDate, $endDate, $cpf = null, $name = null, $value = null)
    {
        $query = "SELECT * FROM transactions WHERE transaction_date BETWEEN :startDate AND :endDate";
        $params = [
            'startDate' => $startDate,
            'endDate' => $endDate
        ];

        if ($cpf) {
            $query .= " AND payer_cpf_cnpj = :cpf";
            $params['cpf'] = $cpf;
        }

        if ($name) {
            $query .= " AND payer_name LIKE :name";
            $params['name'] = '%' . $name . '%';
        }

        if ($value) {
            $query .= " AND value = :value";
            $params['value'] = $value;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
