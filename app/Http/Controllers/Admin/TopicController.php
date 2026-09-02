<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\ActivityLogger;

class TopicController extends Controller
{

    public function index(Request $request)
    {
        $query = Topic::with('category')
            ->withCount('questions');


        if ($request->filled('search')) {

            $query->where(
                'name',
                'like',
                "%{$request->search}%"
            );

        }


        if ($request->filled('category')) {

            $query->where(
                'category_id',
                $request->category
            );

        }


        $topics = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();


        $categories = Category::orderBy('name')
            ->get();


        return view(
            'admin.topics.index',
            compact(
                'topics',
                'categories'
            )
        );
    }





    public function create()
    {

        $categories = Category::orderBy('name')
            ->get();


        return view(
            'admin.topics.create',
            compact('categories')
        );

    }






    public function store(Request $request)
    {

        $validated = $request->validate([

            'name' => [
                'required',
                'string',
                'max:255'
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:topics,slug'
            ],


            'category_id'=>[
                'required',
                'exists:categories,id'
            ]

        ]);



        $topic = Topic::create([

            'name'=>$validated['name'],

            'slug'=>$validated['slug']
                ??
            Str::slug($validated['name']),


            'category_id'=>$validated['category_id']

        ]);

        // Log activity
        ActivityLogger::log('created', 'Created new topic: ' . $topic->name, $topic);



        return redirect()

            ->route('admin.topics.index')

            ->with(
                'success',
                'Topic created successfully.'
            );

    }







    public function show(Topic $topic)
{
    $topic->load('category');


    $questions = $topic->questions()
        ->latest()
        ->paginate(2);



    return view(
        'admin.topics.show',
        compact(
            'topic',
            'questions'
        )
    );
}







    public function edit(Topic $topic)
    {

        $categories = Category::orderBy('name')
            ->get();



        return view(
            'admin.topics.edit',
            compact(
                'topic',
                'categories'
            )
        );

    }







    public function update(
        Request $request,
        Topic $topic
    )
    {

        $validated = $request->validate([


            'name'=>[
                'required',
                'string',
                'max:255'
            ],


            'slug'=>[
                'nullable',
                'string',
                'max:255',
                'unique:topics,slug,'.$topic->id
            ],


            'category_id'=>[
                'required',
                'exists:categories,id'
            ]

        ]);




        $topic->update([

            'name'=>$validated['name'],

            'slug'=>$validated['slug']
                ??
            Str::slug($validated['name']),


            'category_id'=>$validated['category_id']

        ]);

        // Log activity
        ActivityLogger::log('updated', 'Updated topic: ' . $topic->name, $topic);





        return redirect()

            ->route(
                'admin.topics.index'
            )

            ->with(
                'success',
                'Topic updated successfully.'
            );


    }







    public function destroy(Topic $topic)
    {

        if($topic->questions()->exists()){

            return back()
                ->with(
                    'error',
                    'Cannot delete topic with questions.'
                );

        }

        $topicName = $topic->name;

        $topic->delete();

        // Log activity
        ActivityLogger::log('deleted', 'Deleted topic: ' . $topicName);



        return back()

            ->with(
                'success',
                'Topic deleted successfully.'
            );

    }

    
    


}