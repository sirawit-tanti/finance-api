<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Requests\TransactionRequest;
use App\Models\Transaction;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
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

        if ($request->filled('period')) {
            if ($request->period === 'today') {
                $query->whereDate('created_at', today());
            }

            if ($request->period === 'week') {
                $query->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek(),
                ]);
            }

            if ($request->period === 'month') {
                $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
            }

            if ($request->period === 'year') {
                $query->whereYear('created_at', now()->year);
            }
        }

        return TransactionResource::collection(
            $query
                ->latest()
                ->paginate($request->integer('per_page', 10))
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
        $transaction = Transaction::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'title' => $request->title,
            'amount' => $request->amount,
            'type' => $request->type
        ]);

        ActivityLogger::log(
            $request->user()->id,
            'created',
            Transaction::class,
            $transaction->id,
            'Created Transaction',
            [
                'title' => $transaction->title,
                'amount' => $transaction->amount,
                'type' => $transaction->type,
            ]
        );

        return response()->json($transaction, 201);
    }

    public function update(TransactionRequest $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $this->authorize(
            'update',
            $transaction
        );

        $oldData = $transaction->only([
            'title',
            'amount',
            'type',
            'category_id'
        ]);

        $transaction->update(
            $request->validated()
        );

        ActivityLogger::log(
            $request->user()->id,
            'updated',
            Transaction::class,
            $transaction->id,
            'Updated transaction',
            [
                'before' => $oldData,
                'after' => $transaction->fresh()->only([
                    'title',
                    'amount',
                    'type',
                    'category_id'
                ]),
            ]
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

        $deletedData = $transaction->only([
            'title',
            'amount',
            'type',
            'category_id'
        ]);

        $transaction->delete();

        ActivityLogger::log(
            $request->user()->id,
            'deleted',
            Transaction::class,
            $transaction->id,
            'Deleted transaction',
            $deletedData
        );
        
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

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            '*.title' => 'required|max:255',
            '*.amount' => 'required|numeric',
            '*.type' => 'required|in:income,expense',
            '*.category_id' => 'required|exists:categories,id',
        ]);

        $transactions = DB::transaction(function () use ($data, $request) {
            $createdTransactions = [];

            foreach ($data as $item) {
                $createdTransactions[] = Transaction::create([
                    'user_id' => $request->user()->id,
                    'title' => $item['title'],
                    'amount' => $item['amount'],
                    'type' => $item['type'],
                    'category_id' => $item['category_id'],
                ]);
            }

            return $createdTransactions;
        });

        return response()->json([
            'message' => 'Bulk Create Success',
            'count' => count($transactions),
            'data' => $transactions,
        ], 201);
    }
}