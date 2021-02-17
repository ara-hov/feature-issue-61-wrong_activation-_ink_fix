<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feedback;
class FeedbackController extends Controller
{
    public function submitFeedback(Request $request){

		foreach($request->question_id as $key => $question) {
			$data = array(
				'question_id' => $key,
				'answer_rating' => $question,
				'comments' => (is_null($request->feedback)) ? null : $request->feedback,
    			'role' => $request->role,
    			'answer_by' => $request->answer_by,
    			'buying_room_id' => $request->buying_room_id,
			);			
			Feedback::create($data);
		}
		
		// for($i=0;$i<count($request->question_id);$i++){
    	// 	Feedback::create([
    	// 		'question_id' => $request->question_id[$i],
    	// 		'answer_rating' => $request->answer_rating[$i],
    	// 		'role' => $request->role,
    	// 		'answer_by' => $request->answer_by,
    	// 		'buying_room_id' => $request->buying_room_id,
    	// 	]);
    	// }
    	return response()->json(['success' => true, 'message' => 'Feedback added successfully']);
    }
}
