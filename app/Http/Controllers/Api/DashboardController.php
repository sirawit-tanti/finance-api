<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // $summary = Transaction::ownedBy(
        //         $request->user()->id
        //     )->selectRaw('type, SUM(amount) as total')->groupBy('type')->get();

        // $income = $summary->where('type','income')->first()?->total ?? 0;
        // $expense = $summary->where('type','expense')->first()?->total ?? 0;
        
        // return response()->json([
        //     'income' => $income,
        //     'expense' => $expense,
        //     'balance' => $income - $expense
        // ]);
        $period = $request->get('period', 'month');
        $query = Transaction::ownedBy(
            $request->user()->id
        );

        if ($period === 'today') {
            $query->whereDate('created_at', today());
        }

        if ($period === 'week') {
            $query->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ]);
        }

        if ($period === 'month') {
            $query->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year);
        }

        $income = (clone $query)
            ->where('type', 'income')
            ->sum('amount');

        $expense = (clone $query)
            ->where('type', 'expense')
            ->sum('amount');

        $balance = $income - $expense;

        return response()->json([
            'income' => $income,
            'expense' => $expense,
            'balance' => $balance,
            'period' => $period,
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