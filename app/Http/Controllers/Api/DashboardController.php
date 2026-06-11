<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $summary = Transaction::ownedBy(
                $request->user()->id
            )->selectRaw('type, SUM(amount) as total')->groupBy('type')->get();

        $income = $summary->where('type','income')->first()?->total ?? 0;
        $expense = $summary->where('type','expense')->first()?->total ?? 0;
        
        return response()->json([
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense
        ]);
    }

    public function monthly(Request $request)
    {
        $data = Transaction::ownedBy(
            $request->user()->id
        )
        ->selectRaw("
            strftime('%Y-%m', created_at) as month,
            type,
            SUM(amount) as total
        ")
        ->groupBy('month', 'type')
        ->orderBy('month')
        ->get();

        $result = [];

        foreach ($data as $row) {
            if (!isset($result[$row->month])) {
                $result[$row->month] = [
                    'month' => $row->month,
                    'income' => 0,
                    'expense' => 0
                ];
            }

            $result[$row->month][$row->type] = $row->total;
        }
        
        return array_values($result);
    }

    public function category(Request $request) 
    {
        return Transaction::ownedBy(
            $request->user()->id
        )->join(
            'categories',
            'transactions.category_id',
            '=',
            'categories.id'
        )
        ->selectRaw('
            categories.name as category,
            SUM(transactions.amount) as total
        ')
        ->groupBy('categories.name')
        ->orderByDesc('total')
        ->get();
    }
}