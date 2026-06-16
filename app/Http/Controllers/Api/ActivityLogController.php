<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        return ActivityLog::where(
            'user_id',
            $request->user()->id
        )
        ->latest()
        ->paginate(10);
    }

    public function export(Request $request)
    {
        $logs = ActivityLog::where(
            'user_id',
            $request->user()->id
        )
        ->latest()
        ->get();

        $fileName = 'activity_logs.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment: filename={$fileName}",
        ];

        $callback = function () use ($logs) {
          $file = fopen('php://output', 'w');

          fputcsv($file, [
            '#',
            'Action',
            'Model',
            'Model ID',
            'Description',
            'Properties',
            'Created At',
          ]);

          foreach ($logs as $log) {
            fputcsv($file, [
                $log->id,
                $log->action,
                $log->model,
                $log->model_id,
                $log->description,
                json_encode($log->properties),
                $log->created_at,
            ]);
          }

          fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}