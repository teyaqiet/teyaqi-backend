<?php

namespace App\Http\Controllers\Admin\Questions;

use App\Http\Controllers\Controller;
use App\Imports\QuestionsImport;
use App\Services\QuestionImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;

class QuestionImportController extends Controller
{
    /**
     * Show import page.
     */
    public function create()
    {
        return view('admin.questions.import');
    }

    /**
     * Upload and validate the file.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,csv,txt',
                'max:10240',
            ],
        ]);

        $import = new QuestionsImport();

        Excel::import(
            $import,
            $request->file('file')
        );

        $validRows = $import->getValidRows();
        $errors = $import->getErrors();
        $totalRows = $import->getTotalRows();

        /*
         * Count all warnings from valid rows.
         */
        $warningCount = collect($validRows)
            ->sum(
                fn ($row) =>
                    count($row['warnings'] ?? [])
            );

        /*
         * Store validated rows server-side.
         *
         * We intentionally do NOT send the actual
         * question data through hidden HTML inputs.
         */
        Session::put(
            'question_import.valid_rows',
            $validRows
        );

        return view(
            'admin.questions.import-preview',
            [
                'validRows' => $validRows,
                'errors' => $errors,
                'totalRows' => $totalRows,
                'warningCount' => $warningCount,
            ]
        );
    }

    /**
     * Confirm and import the validated questions.
     */
    public function store(
        Request $request,
        QuestionImportService $importService
    ) {
        $validRows = Session::get(
            'question_import.valid_rows',
            []
        );

        if (empty($validRows)) {

            return redirect()
                ->route('admin.questions.import')
                ->with(
                    'error',
                    'There are no validated questions waiting for import.'
                );
        }

        $imported = $importService->import(
            $validRows
        );

        /*
         * Remove temporary import data.
         */
        Session::forget(
            'question_import.valid_rows'
        );

        return redirect()
            ->route('admin.questions.index')
            ->with(
                'success',
                "{$imported} questions imported successfully."
            );
    }
}

