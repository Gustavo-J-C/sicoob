<?php
require_once __DIR__ . '/../config/database.php';

$filterConditions = [];
$filterParams = [];
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 100;
$offset = ($page - 1) * $perPage;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['start_date']) && !empty($_POST['end_date'])) {
        $filterConditions[] = 'horario BETWEEN ? AND ?';
        $filterParams[] = $_POST['start_date'];
        $filterParams[] = $_POST['end_date'];
    }
    if (!empty($_POST['cpf'])) {
        $filterConditions[] = 'chave LIKE ?';
        $filterParams[] = '%' . $_POST['cpf'] . '%';
    }
    if (!empty($_POST['nome'])) {
        $filterConditions[] = 'info_pagador LIKE ?';
        $filterParams[] = '%' . $_POST['nome'] . '%';
    }
    if (!empty($_POST['valor'])) {
        $filterConditions[] = 'valor = ?';
        $filterParams[] = $_POST['valor'];
    }
}

$sql = 'SELECT * FROM transactions';
if (!empty($filterConditions)) {
    $sql .= ' WHERE ' . implode(' AND ', $filterConditions);
}
$sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';

$stmt = $GLOBALS['pdo']->prepare($sql);
$stmt->execute(array_merge($filterParams, [$perPage, $offset]));
$transactions = $stmt->fetchAll();

// Get the total number of transactions for pagination
$countSql = 'SELECT COUNT(*) FROM transactions';
if (!empty($filterConditions)) {
    $countSql .= ' WHERE ' . implode(' AND ', $filterConditions);
}

$countStmt = $GLOBALS['pdo']->prepare($countSql);
$countStmt->execute($filterParams);
$totalTransactions = $countStmt->fetchColumn();
$totalPages = ceil($totalTransactions / $perPage);
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SICOOB PIX</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container-fluid mt-5">
        <!-- Filtro -->
        <form method="POST" class="mt-3">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="start_date">Data Início</label>
                    <input type="date" class="form-control" id="start_date" name="start_date">
                </div>
                <div class="form-group col-md-3">
                    <label for="end_date">Data Fim</label>
                    <input type="date" class="form-control" id="end_date" name="end_date">
                </div>
                <div class="form-group col-md-2">
                    <label for="cpf">CPF</label>
                    <input type="text" class="form-control" id="cpf" name="cpf">
                </div>
                <div class="form-group col-md-2">
                    <label for="nome">Nome</label>
                    <input type="text" class="form-control" id="nome" name="nome">
                </div>
                <div class="form-group col-md-2">
                    <label for="valor">Valor</label>
                    <input type="number" step="0.01" class="form-control" id="valor" name="valor">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>

        <!-- Tabela de Transações -->
        <table class="table table-striped table-bordered contact-list table-hover mt-3">
            <thead class="thead-dark">
                <tr>
                    <th>Data - Hora</th>
                    <th>CPF</th>
                    <th>Nome</th>
                    <th>Chave</th>
                    <th>Valor</th>
                    <th>Status</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= date('d/m/Y H:i:s', strtotime($transaction['horario'])) ?></td>
                        <td><?= $transaction['chave'] ?></td>
                        <td><?= $transaction['info_pagador'] ?></td>
                        <td><?= $transaction['chave'] ?></td>
                        <td><?= $transaction['valor'] ?></td>
                        <?php
                        $devolucoes_sql = "SELECT * FROM devolucoes WHERE transaction_id = ?";
                        $devolucoes_stmt = $GLOBALS['pdo']->prepare($devolucoes_sql);
                        $devolucoes_stmt->execute([$transaction['end_to_end_id']]);
                        $devolucoes = $devolucoes_stmt->fetchAll();
                        
                        // Verifica se algum status é 'devolvido'
                        $status = 'recebido'; // Default status
                        foreach ($devolucoes as $devolucao) {
                            if (strtolower($devolucao['status']) === 'devolvido') {
                                $status = 'extornado';
                                break;
                            }
                        }
                        $class = $status === 'extornado' ? 'bg-danger' : 'bg-success';
                        
                        echo '<td class="badge ' . $class . ' ml-3">' . $status . '</td>';
                        ?>
                        <td>
                            <button type="button" class="btn btn-info btn-sm" data-toggle="modal"
                                data-target="#modalDetalhes<?= $transaction['id'] ?>">
                                Ver Detalhes
                            </button>

                            <!-- Modal -->
                            <div class="modal fade" id="modalDetalhes<?= $transaction['id'] ?>" tabindex="-1" role="dialog"
                                aria-labelledby="modalDetalhesLabel<?= $transaction['id'] ?>" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="modalDetalhesLabel<?= $transaction['id'] ?>">
                                                Detalhes da Transação <?= $transaction['id'] ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <!-- <p><strong>ID End-to-End:</strong> <?= $transaction['end_to_end_id'] ?></p> -->
                                            <p><strong>Valor:</strong> <?= $transaction['valor'] ?></p>
                                            <p><strong>Horário:</strong> <?= date('d/m/Y H:i:s', strtotime($transaction['horario'])) ?></p>
                                            <p><strong>Chave:</strong> <?= $transaction['chave'] ?></p>
                                            <!-- <p><strong>TXID:</strong> <?= $transaction['txid'] ?></p> -->
                                            <p><strong>Info Pagador:</strong> <?= $transaction['info_pagador'] ?></p>
                                            <!-- <p><strong>Raw Data:</strong> <?= htmlspecialchars($transaction['raw_data']) ?></p> -->
                                            <hr>
                                            <h5>Devoluções</h5>
                                            <ul class="list-group">
                                                <?php foreach ($devolucoes as $devolucao): ?>
                                                    <li class="list-group-item">
                                                        <strong>Status:</strong> <?= $devolucao['status'] ?><br>
                                                        <strong>Valor:</strong> <?= $devolucao['valor'] ?><br>
                                                        <strong>Motivo:</strong> <?= $devolucao['motivo'] ?><br>
                                                        <strong>Solicitação:</strong> <?= $devolucao['solicitacao'] ?><br>
                                                        <strong>Liquidação:</strong> <?= $devolucao['liquidacao'] ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary"
                                                data-dismiss="modal">Fechar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginação -->
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>