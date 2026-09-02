<?php

namespace App\Http\Controllers\Admin\Questions;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\ActivityLogger;
use Illuminate\Support\Str;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $query = Question::with('category');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('question_text', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $allowedSorts = [
            'question_text',
            'category_id',
            'difficulty',
            'difficulty_score',
            'times_shown',
            'times_correct',
            'created_at',
        ];

        $sort = $request->get('sort', 'created_at');
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'created_at';
        }

        $direction = $request->get('direction', 'desc');
        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        $questions = $query
            ->orderBy($sort, $direction)
            ->paginate(20)
            ->withQueryString();

        $categories = Category::orderBy('name')->get();

        return view('admin.questions.index', compact('questions', 'categories'));
    }

    public function show(Question $question)
    {
        $question->load('category');

        return view('admin.questions.show', compact('question'));
    }

    public function edit(Question $question)
    {
        $question->load('category');
        $categories = Category::orderBy('name')->get();

        return view('admin.questions.edit', [
            'question' => $question,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Question $question)
    {
        $validated = $request->validate([
            'category_id'      => 'required|exists:categories,id',
            'question_text.en' => 'required|string',
            'question_text.am' => 'nullable|string',
            'option_a.en'      => 'required|string',
            'option_a.am'      => 'nullable|string',
            'option_b.en'      => 'required|string',
            'option_b.am'      => 'nullable|string',
            'option_c.en'      => 'required|string',
            'option_c.am'      => 'nullable|string',
            'option_d.en'      => 'required|string',
            'option_d.am'      => 'nullable|string',
            'correct_answer'   => 'required|in:a,b,c,d',
            'difficulty'       => 'required|in:easy,medium,hard',
            'is_active'        => 'required|boolean',
            'explanation.en'   => 'nullable|string',
            'explanation.am'   => 'nullable|string',
            'image'            => 'nullable|image|max:5120',
        ]);

        $data = [
            'category_id'   => $validated['category_id'],
            'question_text' => [
                'en' => $validated['question_text']['en'],
                'am' => $validated['question_text']['am'] ?? '',
            ],
            'option_a' => [
                'en' => $validated['option_a']['en'],
                'am' => $validated['option_a']['am'] ?? '',
            ],
            'option_b' => [
                'en' => $validated['option_b']['en'],
                'am' => $validated['option_b']['am'] ?? '',
            ],
            'option_c' => [
                'en' => $validated['option_c']['en'],
                'am' => $validated['option_c']['am'] ?? '',
            ],
            'option_d' => [
                'en' => $validated['option_d']['en'],
                'am' => $validated['option_d']['am'] ?? '',
            ],
            'correct_answer' => $validated['correct_answer'],
            'difficulty'     => $validated['difficulty'],
            'explanation'    => [
                'en' => $validated['explanation']['en'] ?? '',
                'am' => $validated['explanation']['am'] ?? '',
            ],
            'is_active'      => $validated['is_active'],
        ];

        if ($request->hasFile('image')) {
            if ($question->getRawOriginal('image_url')) {
                Storage::disk('public')->delete($question->getRawOriginal('image_url'));
            }

            $path = $request->file('image')->store('questions', 'public');
            $data['image_url'] = $path;
        }

        $question->update($data);

        // Log activity
        ActivityLogger::log('updated', 'Updated question: ' . Str::limit($validated['question_text']['en'], 50), $question);

        return redirect()
            ->route('admin.questions.show', $question)
            ->with('success', 'Question updated successfully.');
    }

    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.questions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id'      => ['required', 'exists:categories,id'],
            'question_text.en' => ['required', 'string'],
            'question_text.am' => ['nullable', 'string'],
            'option_a.en'      => ['required', 'string'],
            'option_a.am'      => ['nullable', 'string'],
            'option_b.en'      => ['required', 'string'],
            'option_b.am'      => ['nullable', 'string'],
            'option_c.en'      => ['required', 'string'],
            'option_c.am'      => ['nullable', 'string'],
            'option_d.en'      => ['required', 'string'],
            'option_d.am'      => ['nullable', 'string'],
            'correct_answer'   => ['required', 'in:a,b,c,d'],
            'difficulty'       => ['required', 'in:easy,medium,hard'],
            'is_active'        => ['required', 'boolean'],
            'explanation.en'   => ['nullable', 'string'],
            'explanation.am'   => ['nullable', 'string'],
            'image'            => ['nullable', 'image', 'max:5120'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('questions', 'public');
        }

        $question = Question::create([
            'category_id'   => $validated['category_id'],
            'question_text' => [
                'en' => $validated['question_text']['en'],
                'am' => $validated['question_text']['am'] ?? '',
            ],
            'option_a' => [
                'en' => $validated['option_a']['en'],
                'am' => $validated['option_a']['am'] ?? '',
            ],
            'option_b' => [
                'en' => $validated['option_b']['en'],
                'am' => $validated['option_b']['am'] ?? '',
            ],
            'option_c' => [
                'en' => $validated['option_c']['en'],
                'am' => $validated['option_c']['am'] ?? '',
            ],
            'option_d' => [
                'en' => $validated['option_d']['en'],
                'am' => $validated['option_d']['am'] ?? '',
            ],
            'correct_answer'   => $validated['correct_answer'],
            'difficulty'       => $validated['difficulty'],
            'difficulty_score' => 50,
            'explanation'      => [
                'en' => $validated['explanation']['en'] ?? '',
                'am' => $validated['explanation']['am'] ?? '',
            ],
            'image_url'        => $imagePath,
            'is_active'        => $validated['is_active'],
        ]);

        // Log activity
        ActivityLogger::log('created', 'Created new question: ' . Str::limit($validated['question_text']['en'], 50), $question);

        $action = $request->input('action', 'save');

        return match ($action) {
            'save_and_show' => redirect()
                ->route('admin.questions.show', $question)
                ->with('success', 'Question created successfully!'),

            'save_and_create' => redirect()
                ->route('admin.questions.create')
                ->with('success', 'Question created successfully! You can add another one now.'),

            default => redirect()
                ->route('admin.questions.index')
                ->with('success', 'Question created successfully!'),
        };
    }

    public function destroy(Question $question)
    {
        if ($question->getRawOriginal('image_url')) {
            Storage::disk('public')->delete($question->getRawOriginal('image_url'));
        }

        $questionText = is_array($question->question_text) ? ($question->question_text['en'] ?? 'Unknown') : $question->question_text;

        $question->delete();

        // Log activity
        ActivityLogger::log('deleted', 'Deleted question: ' . Str::limit($questionText, 50));

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Question deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'questions'   => ['required', 'array'],
            'questions.*' => ['exists:questions,id'],
        ]);

        $questions = Question::whereIn('id', $request->questions)->get();

        foreach ($questions as $question) {
            if ($question->getRawOriginal('image_url')) {
                Storage::disk('public')->delete($question->getRawOriginal('image_url'));
            }
            $question->delete();
        }

        // Log activity
        ActivityLogger::log('deleted', 'Bulk deleted ' . count($questions) . ' questions.');

        return redirect()
            ->route('admin.questions.index')
            ->with('success', count($questions) . ' questions deleted successfully.');
    }
}