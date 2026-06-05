<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Models\TransactionPoints;
use Illuminate\Http\Request;

class TransactionPointsController extends BaseController
{
    public function index(Request $request)
    {
        $query = TransactionPoints::query()->with(['client', 'reservation']);

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        return $this->success($query->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'reservation_id' => 'nullable|exists:reservations,id',
            'type_operation' => 'required|in:credit,debit,expiration',
            'points'         => 'required|integer|min:1',
        ]);

        $client = Client::findOrFail($data['client_id']);

        $transaction = TransactionPoints::create($data);

        if ($data['type_operation'] === 'credit') {
            $client->increment('points_fidelite', $data['points']);
        } else {
            $client->decrement('points_fidelite', min($data['points'], $client->points_fidelite));
        }

        return $this->success($transaction->load(['client', 'reservation']), 'Transaction enregistree.', 201);
    }

    public function show(Client $client)
    {
        return $this->success([
            'client'       => $client->only(['id', 'nom', 'prenom', 'points_fidelite']),
            'transactions' => $client->transactionPoints()->latest()->get(),
        ]);
    }
}
