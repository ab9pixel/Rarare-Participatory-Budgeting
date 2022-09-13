<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ParticipatoryBudget;
use App\Models\Like;
use App\Models\Option;
use App\Models\UserOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParticipatoryBudgetController extends Controller
{

    public function list($count, $user_id,$type)
    {
        if ($count != 0) {
            if ($type == "l") {
                $participatory_budget = ParticipatoryBudget::withCount('comments', 'likes')->with('comments', 'options')->orderBy('status', 'desc')->orderBy('created_at', 'desc')->limit($count)->get();
            } else {
                $participatory_budget = ParticipatoryBudget::withCount('comments', 'likes')->with('comments', 'options')->where('user_id', $user_id)->orderBy('status', 'desc')->orderBy('created_at', 'desc')->limit($count)->get();
            }
        } else {
            if ($type == "l") {
                $participatory_budget = ParticipatoryBudget::withCount('comments', 'likes')->with('comments', 'options')->orderBy('status', 'desc')->orderBy('created_at', 'desc')->get();
            } else {
                $participatory_budget = ParticipatoryBudget::withCount('comments', 'likes')->with('comments', 'options')->where('user_id', $user_id)->orderBy('status', 'desc')->orderBy('created_at', 'desc')->get();
            }
        }
        
        
        $data = [];
	    if ($type == "l") {
		    $user = $this->get_user($user_id);
		    if (!is_null($user->latitude)) {
			    foreach ($participatory_budget as $key => $budget) {
				    $source = [
					    'lat' => $budget->latitude,
					    'lng' => $budget->longitude
				    ];

				    $destination = [
					    'lat' => $user->latitude,
					    'lng' => $user->longitude
				    ];

				    $mile = $this->calculate_distance($source, $destination);

				    if ($mile > 30) {
					    $participatory_budget->forget($key);
				    } else {
					    array_push($data, $budget);
				    }
			    }
		    }
	    } else {
		    $data = $participatory_budget;
	    }

	    return response()->json($data);
    }

    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:255',
            'description' => 'required',
            'address' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            'audience' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
            'user_id' => 'required',
            'participation' => 'required',
            'vote_question' => 'required',
            'budget' => 'required',
            'proposed_summary' => 'required',
            'budget_benefits' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages()->all();

            return response()->json([
                'status' => 'Error',
                'message' => $messages[0],
            ], 200);
        }

        if (isset($request->id)) {
            $participatory_budget = ParticipatoryBudget::find($request->id);
        } else {
            $participatory_budget = new ParticipatoryBudget;
        }

        $participatory_budget->title = $request->title;
        $participatory_budget->description = $request->description;
        $participatory_budget->address = $request->address;
        $participatory_budget->latitude = $request->latitude;
        $participatory_budget->longitude = $request->longitude;
        $participatory_budget->audience = $request->audience;
        $participatory_budget->start_date = $request->start_date;
        $participatory_budget->end_date = $request->end_date;
        $participatory_budget->start_time = $request->start_time;
        $participatory_budget->end_time = $request->end_time;
        $participatory_budget->participation = $request->participation;
        $participatory_budget->vote_question = $request->vote_question;
        $participatory_budget->budget = $request->budget;
        $participatory_budget->proposed_summary = $request->proposed_summary;
        $participatory_budget->budget_benefits = $request->budget_benefits;
        $participatory_budget->user_id = $request->user_id;
        if ($participatory_budget->save()) {
            if (!isset($request->id)) {
                foreach ($request->vote_option as $key => $vote_option) {
                    $option = new Option;
                    $option->parent_id = $participatory_budget->id;
                    $option->vote_option = $vote_option;
                    $option->vote_description = $request->vote_description[$key];
                    $option->save();
                }
            }
        }

        return response()->json($participatory_budget);
    }

    public function find($id)
    {
        $participatory_budget = ParticipatoryBudget::with('comments', 'options')->withCount('comments', 'likes')->find($id);
        return response()->json($participatory_budget);
    }

    public function comment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'comment' => 'required',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages()->all();

            return response()->json([
                'status' => 'Error',
                'message' => $messages[0],
            ], 200);
        }

        $comment = new Comment;
        $comment->user_id = $request->user_id;
        $comment->parent_id = $request->parent_id;
        $comment->comment = $request->comment;
        $comment->save();

        $comments = Comment::where('parent_id', $request->parent_id)->get();

        $participatory_budget = ParticipatoryBudget::find($request->parent_id);
        if ($participatory_budget->participation == 1) {
            $post['user_id'] = $participatory_budget->user_id;
            $post['action'] = "Commented";
            $post['type'] = "Participatory Budget";
            $post['vote_question'] = $participatory_budget->vote_question;
            $post['message'] = $participatory_budget->description;
            $post['url'] = "https://staging.rarare.com/budget-proposal?id=" . $request->parent_id;
            $post['title'] = $participatory_budget->title;
            $post['sender_id'] = $request->user_id;
            $this->send_notification($post);
        }
        return response()->json($comments);
    }

    public function like(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages()->all();

            return response()->json([
                'status' => 'Error',
                'message' => $messages[0],
            ], 200);
        }

        $liked = Like::where(['user_id' => $request->user_id, 'parent_id' => $request->parent_id])->first();
        if (!is_null($liked)) {
            $liked->delete();
            $likes = Like::where(['parent_id' => $request->parent_id])->count();

            return response()->json($likes);
        }

        $like = new Like;
        $like->user_id = $request->user_id;
        $like->parent_id = $request->parent_id;
        $like->save();

        $likes = Like::where(['parent_id' => $request->parent_id])->count();

        $participatory_budget = ParticipatoryBudget::find($request->parent_id);
        if ($participatory_budget->participation == 1) {
            $post['user_id'] = $participatory_budget->user_id;
            $post['action'] = "Liked";
            $post['type'] = "Participatory Budget";
            $post['vote_question'] = $participatory_budget->vote_question;
            $post['message'] = $participatory_budget->description;
            $post['url'] = "https://staging.rarare.com/budget-proposal?id=" . $request->parent_id;
            $post['title'] = $participatory_budget->title;
            $post['sender_id'] = $request->user_id;

            $this->send_notification($post);
        }

        return response()->json($likes);
    }

    public function user_option(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'parent_id' => 'required',
            'option_id' => 'required',
        ]);

        if ($validator->fails()) {
            $messages = $validator->messages()->all();

            return response()->json([
                'status' => 'Error',
                'message' => $messages[0],
            ], 200);
        }

        $user_option = new UserOption;
        $user_option->user_id = $request->user_id;
        $user_option->parent_id = $request->parent_id;
        $user_option->option_id = $request->option_id;
        $user_option->save();

        $participatory_budget = ParticipatoryBudget::find($request->parent_id);

        $option_array = array();
        $option = Option::where(['parent_id' => $request->parent_id])->get();

        if ($participatory_budget->audience <= count($option)) {
            $participatory_budget->status = 1;
            $participatory_budget->save();
        }

        foreach ($option as $item) {
            $option_array[$item->id] = count(UserOption::where(['option_id' => $item->id])->get());
        }

        $participatory_budget = ParticipatoryBudget::find($request->parent_id);
        if ($participatory_budget->participation == 1) {
            $post['user_id'] = $participatory_budget->user_id;
            $post['action'] = "Voted";
            $post['type'] = "Participatory Budget";
            $post['vote_question'] = $participatory_budget->vote_question;
            $post['message'] = $participatory_budget->description;
            $post['url'] = "https://staging.rarare.com/budget-proposal?id=" . $request->parent_id;
            $post['title'] = $participatory_budget->title;
            $post['sender_id'] = $request->user_id;
            $this->send_notification($post);
        }

        return response()->json($option_array);
    }

    public function delete($id)
    {
        $participatory_budget = ParticipatoryBudget::with('comments', 'options', 'likes', 'user_option')->find($id);
        if (!is_null($participatory_budget)) {
            $participatory_budget->comments()->delete();
            $participatory_budget->user_option()->delete();
            $participatory_budget->likes()->delete();
            $participatory_budget->options()->delete();
            $participatory_budget->delete();
        }
        return response()->json(['message' => 'Deleted']);
    }

    public function send_notification($post)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://rrci.staging.rarare.com/proposal/subscribe/email',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array(
                'title' => $post['title'],
                'type' => $post['type'],
                'vote_question' => $post['vote_question'],
                'message' => $post['message'],
                'action' => $post['action'],
                'url' => $post['url'],
                'user_id' => $post['user_id'],
                'sender_id' => $post['sender_id']
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return true;
    }

    public function get_user($id)
	{
		$curl = curl_init();

		curl_setopt_array($curl, array(
			CURLOPT_URL => 'https://rrci.staging.rarare.com/user/' . $id,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING => '',
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => 'GET',
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return json_decode($response);
	}

	function calculate_distance($source, $destination)
	{
		$lat1  = floatval($source['lat']);
		$lon1  = floatval($source['lng']);
		$lat2  = floatval($destination['lat']);
		$lon2  = floatval($destination['lng']);
		$theta = $lon1 - $lon2;
		$dist  = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
		$dist  = acos($dist);
		$dist  = rad2deg($dist);
		$miles = $dist * 60 * 1.1515;

		return $miles;
	}
}
