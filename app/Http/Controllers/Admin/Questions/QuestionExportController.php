<?php

namespace App\Http\Controllers\Admin\Questions;

use App\Exports\QuestionsExport;
use App\Exports\QuestionsTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class QuestionExportController extends Controller
{
    /**
     * Download the Excel template.
     */
    public function template()
    {
        return Excel::download(
            new QuestionsTemplateExport,
            'teyaqi_questions_import_template.xlsx'
        );
    }

    /**
     * Show export page.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.questions.export', [
            'categories' => $categories,
        ]);
    }

    /**
     * Export questions using filters.
     */
    public function export(Request $request)
    {
        $request->validate([
            'category' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],

            'difficulty' => [
                'nullable',
                'in:easy,medium,hard',
            ],

            'status' => [
                'nullable',
                'in:active,inactive',
            ],
        ]);

        return Excel::download(
            new QuestionsExport(
                categoryId: $request->input('category'),
                difficulty: $request->input('difficulty'),
                status: $request->input('status'),
            ),
            'teyaqi_questions_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }

    /**
     * Export only selected questions.
     */
    public function exportSelected(Request $request)
    {
        $request->validate([
            'questions' => [
                'required',
                'array',
                'min:1',
            ],

            'questions.*' => [
                'integer',
                'exists:questions,id',
            ],
        ]);

        $questionIds = $request->input('questions');

        return Excel::download(
            new QuestionsExport(
                questionIds: $questionIds
            ),
            'teyaqi_selected_questions_' . now()->format('Y-m-d_H-i-s') . '.xlsx'
        );
    }
}