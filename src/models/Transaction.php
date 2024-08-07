<?php

class Transaction
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function save($data)
    {
        try {
            $endToEndId = $data['endToEndId'];
            $valor = $data['valor'];
            $horario = $this->formatDate($data['horario']);
            $chave = $data['chave'];
            $txid = $data['txid'];
            $infoPagador = $data['infoPagador'];
            $status = isset($data['devolucoes']) && count($data['devolucoes']) > 0 ? 'DEVOLVIDO' : 'RECEBIDO';

            // Check if the transaction already exists
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM transactions WHERE end_to_end_id = ?");
            $stmt->execute([$endToEndId]);
            if ($stmt->fetchColumn() > 0) {
                return; // Record already exists, skip insertion
            }

            $stmt = $this->pdo->prepare("INSERT INTO transactions (end_to_end_id, valor, horario, chave, txid, info_pagador) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$endToEndId, $valor, $horario, $chave, $txid, $infoPagador]);

            // Save devoluções if present
            if (isset($data['devolucoes'])) {
                foreach ($data['devolucoes'] as $devolucao) {
                    $stmt = $this->pdo->prepare("INSERT INTO devolucoes (transaction_id, rtr_id, valor, solicitacao, liquidacao, motivo, status, devolucao_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$endToEndId, $devolucao['rtrId'], $devolucao['valor'], $this->formatDate($devolucao['horario']['solicitacao']), $this->formatDate($devolucao['horario']['liquidacao']), $devolucao['motivo'], $devolucao['status'], $devolucao['id']]);
                }
            }
        } catch (Exception $e) {
            throw new Exception("Error saving transaction: " . $e->getMessage());
        }
    }

    public function getLastTransactionDate()
    {
        try {
            $stmt = $this->pdo->query("SELECT MAX(horario) AS last_update FROM transactions");
            return $stmt->fetchColumn() ?: '1997-01-01T00:00:00Z';
        } catch (Exception $e) {
            throw new Exception("Error fetching last transaction date: " . $e->getMessage());
        }
    }

    private function formatDate($date)
    {
        return preg_replace('/\.\d+Z$/', '', $date);
    }
}
?>
