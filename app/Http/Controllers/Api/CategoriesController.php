<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::query();

        if ($request->filled('search')) {
            $query->where(
                'name',
                'like',
                '%'.$request->search.'%'
            );
        }

        return $query
            ->latest()
            ->paginate(
                $request->integer('per_page', 10)
            );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255|unique:categories,name',
        ]);

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|max:255|unique:categories,name,' . $category->id,
        ]);

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    public function export()
    {
        $categories = Category::latest()
            ->get();

        $fileName = 'categories.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($categories) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                '#',
                'Name',
                'Created At',
            ]);

            foreach ($categories as $category) {
                fputcsv($file, [
                    $category->id,
                    $category->name,
                    $category->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ]);
    }
}