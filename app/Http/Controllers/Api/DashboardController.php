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
        $period = $request->get('period', 'month');

        $query = Transaction::where('user_id', $request->user()->id);

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

        if ($period === 'year') {
            $query->whereYear('created_at', now()->year);
        }

        if (config('database.default') === 'sqlite') {
            return $query
                ->selectRaw("strftime('%m', created_at) as month_number")
                ->selectRaw("
                    CASE strftime('%m', created_at)
                        WHEN '01' THEN 'Jan'
                        WHEN '02' THEN 'Feb'
                        WHEN '03' THEN 'Mar'
                        WHEN '04' THEN 'Apr'
                        WHEN '05' THEN 'May'
                        WHEN '06' THEN 'Jun'
                        WHEN '07' THEN 'Jul'
                        WHEN '08' THEN 'Aug'
                        WHEN '09' THEN 'Sep'
                        WHEN '10' THEN 'Oct'
                        WHEN '11' THEN 'Nov'
                        WHEN '12' THEN 'Dec'
                    END as month
                ")
                ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
                ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
                ->groupBy('month_number', 'month')
                ->orderBy('month_number')
                ->get();
        }

        return $query
            ->selectRaw('MONTH(created_at) as month_number')
            ->selectRaw("DATE_FORMAT(created_at, '%b') as month")
            ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income")
            ->selectRaw("SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
            ->groupBy('month_number', 'month')
            ->orderBy('month_number')
            ->get();
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

    public function overview(Request $request)
    {
        return response()->json([
            'summary' => $this->index($request)->getData(true),
            'monthly' => $this->monthly($request),
            'category' => $this->category($request),
            'recent_transactions' => Transaction::ownedBy($request->user()->id)
                ->with('category')
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }
}