<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\ParticipatoryBudget;
use App\Models\Like;
use App\Models\Option;
use App\Models\UserOption;
use Illuminate\Http\Request;

class ParticipatoryBudgetController extends Controller
{

    public function list()
    {
        $participatory_budget = ParticipatoryBudget::withCount('comments', 'likes')->with('comments', 'options')->get();
        return response()->json($participatory_budget);
    }

    public function save(Request $request)
    {
        $this->validate($request, [
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
            'vote_question'=> 'required',
            'budget'=> 'required',
            'proposed_summary'=> 'required',
            'budget_benefits'=> 'required',
        ]);

        if(isset($request->id)){
            $participatory_budget = ParticipatoryBudget::find($request->id);
        }
        else{
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
            if(!isset($request->id)){
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
        $this->validate($request, [
            'user_id' => 'required',
            'comment' => 'required',
            'parent_id' => 'required',
        ]);

        $comment = new Comment;
        $comment->user_id = $request->user_id;
        $comment->parent_id = $request->parent_id;
        $comment->comment = $request->comment;
        $comment->save();

        return response()->json($comment);
    }

    public function like(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required',
            'parent_id' => 'required',
        ]);
        $liked=Like::where(['user_id'=>$request->user_id,'parent_id'=>$request->parent_id])->first();
        if(!is_null($liked)){
            $liked->delete();
            return response()->json(['message'=>'Disliked']);
        }
        
        $like = new Like;
        $like->user_id = $request->user_id;
        $like->parent_id = $request->parent_id;
        $like->save();

        return response()->json($like);
    }

    public function user_option(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required',
            'parent_id' => 'required',
            'option_id' => 'required',
        ]);

        $user_option = new UserOption;
        $user_option->user_id = $request->user_id;
        $user_option->parent_id = $request->parent_id;
        $user_option->option_id = $request->option_id;
        $user_option->save();

        return response()->json($user_option);
    }

    public function delete($id)
    {
        $participatory_budget = ParticipatoryBudget::with('comments', 'options','likes','user_option')->find($id);
        if(!is_null($participatory_budget)){
            $participatory_budget->comments()->delete();
            $participatory_budget->user_option()->delete();
            $participatory_budget->likes()->delete();
            $participatory_budget->options()->delete();
            $participatory_budget->delete();
        }
        return response()->json(['message'=>'Deleted']);
    }
}
