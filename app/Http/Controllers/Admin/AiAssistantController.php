<?php



namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\AI\Agents\TeyaqiAdminAgent;
use App\AI\Memory\ConversationMemory;
use Illuminate\Http\Request;
use Throwable;

class AiAssistantController extends Controller
{

    public function chat(
        Request $request,
        TeyaqiAdminAgent $agent,
        ConversationMemory $memory
    )
    {

        try {

            $session = session()->getId();


            $memory->load($session);



            $memory->addMessage(
                'user',
                $request->message
            );



            $response =
            $agent->handle(
                $request->message
            );



            $memory->addMessage(
                'assistant',
                $response['text']
            );



            return response()->json($response);



        } catch(Throwable $e) {


            return response()->json([

                "error" => true,

                "message" => $e->getMessage(),

                "file" => $e->getFile(),

                "line" => $e->getLine(),

                "trace" => $e->getTraceAsString()

            ],500);


        }

    }

}