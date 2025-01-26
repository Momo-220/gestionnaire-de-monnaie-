<?php

namespace App\Controllers;

use App\Models\Transaction;
use App\Auth\AuthManager;

class TransactionController
{
    private $transaction;
    private $auth;

    public function __construct()
    {
        $this->transaction = new Transaction();
        $this->auth = AuthManager::getInstance();
    }

    public function addTransaction()
    {
        if (!$this->auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit;
        }

        $user = $this->auth->getCurrentUser();
        $data = [
            'amount' => filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT),
            'type' => filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING),
            'category' => filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING),
            'description' => filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'date' => filter_input(INPUT_POST, 'date', FILTER_SANITIZE_STRING)
        ];

        if (!$data['amount'] || !in_array($data['type'], ['income', 'expense'])) {
            $_SESSION['error'] = 'Données invalides';
            header('Location: /dashboard');
            exit;
        }

        if ($this->transaction->create($user['id'], $data)) {
            $_SESSION['success'] = 'Transaction ajoutée avec succès';
        } else {
            $_SESSION['error'] = 'Erreur lors de l\'ajout de la transaction';
        }

        header('Location: /dashboard');
        exit;
    }

    public function deleteTransaction()
    {
        if (!$this->auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /dashboard');
            exit;
        }

        $user = $this->auth->getCurrentUser();
        $transactionId = filter_input(INPUT_POST, 'transaction_id', FILTER_SANITIZE_STRING);

        if (!$transactionId) {
            $_SESSION['error'] = 'ID de transaction invalide';
            header('Location: /dashboard');
            exit;
        }

        if ($this->transaction->delete($transactionId, $user['id'])) {
            $_SESSION['success'] = 'Transaction supprimée avec succès';
        } else {
            $_SESSION['error'] = 'Erreur lors de la suppression de la transaction';
        }

        header('Location: /dashboard');
        exit;
    }

    public function exportTransactions($format = 'csv')
    {
        if (!$this->auth->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $user = $this->auth->getCurrentUser();
        $transactions = $this->transaction->getUserTransactions($user['id']);

        if ($format === 'csv') {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="transactions.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Type', 'Catégorie', 'Description', 'Montant']);

            foreach ($transactions as $transaction) {
                fputcsv($output, [
                    $transaction['date'],
                    $transaction['type'] === 'income' ? 'Revenu' : 'Dépense',
                    $transaction['category'],
                    $transaction['description'],
                    $transaction['amount']
                ]);
            }

            fclose($output);
            exit;
        } elseif ($format === 'pdf') {
            // Utilisation de DOMPDF pour générer le PDF
            $dompdf = new \Dompdf\Dompdf();
            
            $html = '<h1>Transactions</h1>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<tr><th>Date</th><th>Type</th><th>Catégorie</th><th>Description</th><th>Montant</th></tr>';

            foreach ($transactions as $transaction) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($transaction['date']) . '</td>';
                $html .= '<td>' . ($transaction['type'] === 'income' ? 'Revenu' : 'Dépense') . '</td>';
                $html .= '<td>' . htmlspecialchars($transaction['category']) . '</td>';
                $html .= '<td>' . htmlspecialchars($transaction['description']) . '</td>';
                $html .= '<td>' . number_format($transaction['amount'], 2) . ' €</td>';
                $html .= '</tr>';
            }

            $html .= '</table>';

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream('transactions.pdf');
            exit;
        }

        header('Location: /dashboard');
        exit;
    }
} 