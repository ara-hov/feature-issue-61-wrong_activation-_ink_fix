<?php

namespace App\Http\Controllers\Questionnaire;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Questionnaire;

class QuestionnaireController extends Controller
{
    public function getAllQuestions($role_id, $buying_room_id)
    {
        $answer_by = auth()->id();
        $feedback = Feedback::where(['answer_by' => $answer_by, 'buying_room_id' => $buying_room_id])->first();
        if($feedback) {
            return response()->json(['success' => false, 'data' => [], 'message' => 'Feedback already submitted !']);    
        }
        
        $questions = Questionnaire::where('role', $role_id)->orderBy('order', 'asc')->get();
        $role = $role_id;

        if ($questions->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No Questionnaire available !']);
        }
		
		$data = ['questions' => $questions, 'buying_room_id' => $buying_room_id];
		
        return response()->json(['success' => true, 'data' => $data, 'message' => 'Questionnaire available !']);
    }
}
