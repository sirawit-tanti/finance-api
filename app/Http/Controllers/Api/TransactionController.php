<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $query = Transaction::with('category')
        ->ownedBy(
            $request->user()->id
        );

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->type
            );
        }

        if ($request->filled('search')) {
            $query->where(
                'title',
                'like',
                '%' . $request->search . '%'
            );
        }

        if ($request->filled('month')) {
            $query->whereMonth(
                'created_at',
                $request->month
            );
        }

        return TransactionResource::collection(
            $query
                ->latest()
                ->paginate($perPage)
        );
    }

    public function show(Request $request, $id) {
        $transaction = Transaction::with('category')
        ->ownedBy(
            $request->user()->id
        )->where(
            'id',
            $id
        )->firstOrFail();

        return new TransactionResource($transaction);
    }

    public function store(TransactionRequest $request)
    {
        return Transaction::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'type' => $request->type
        ]);
    }

    public function update(TransactionRequest $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $this->authorize(
            'update',
            $transaction
        );

        $transaction->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Update Success',
            'data' => $transaction
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $transaction = Transaction::ownedBy(
            $request->user()->id
        )->where(
            'id',
            $id
        )->firstOrFail();

        $this->authorize(
            'delete',
            $transaction
        );

        $transaction->delete();
        
        return response()->json([
            'message' => 'Delete Success',
        ]);
    }

    public function export(Request $request)
    {
        $transactions = Transaction::ownedBy(
            $request->user()->id
        )->get();

        $fileName = 'transaction.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' =>
                "attachment; filename={$fileName}",
        ];

        $callback = function () use ($transactions) {
            $file = fopen(
                'php://output',
                'w'
            );

            fputcsv(
                $file,
                [
                    'Title',
                    'Amount',
                    'Type',
                    'Created At'
                ]
            );

            foreach ($transactions as $transaction) {
                fputcsv(
                    $file,
                    [
                        $transaction->title,
                        $transaction->amount,
                        $transaction->type,
                        $transaction->created_at,
                    ]
                );
            }

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }
}