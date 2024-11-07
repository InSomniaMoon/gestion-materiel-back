<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemOption;
use App\Models\ItemOptionIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ItemOptionIssueController extends Controller
{
    function createIssue(Request $request, ItemOption $itemOption)
    {
        $validator = Validator::make($request->all(), [
            'date_declaration' => 'required|date',
            'status' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $issue = $itemOption->optionIssues()->create([
            'date_declaration' => $request->date_declaration,
            'status' => $request->status,
        ]);

        return response()->json($issue, 201);
    }

    public function getComments(ItemOption $option, ItemOptionIssue $optionIssue)
    {
        return response()->json($optionIssue->comments()->with('author:id,name')->get());
    }

    public function createComment(Request $request, ItemOption $option, ItemOptionIssue $optionIssue)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $comment = $optionIssue->comments()->create([
            'comment' => $request->comment,
            'user_id' => Auth::user()->id,
        ]);

        return response()->json($comment, 201);
    }

    public function getIssues(Item $item)
    {
        return response()->json($item->options()->with(['optionIssues' => function ($q) {
            $q->where('status', 'open');
        }])->get());
    }

    public function resolveIssue(ItemOption $option, ItemOptionIssue $optionIssue)
    {
        $optionIssue->status = 'resolved';
        $optionIssue->save();

        return response()->json($optionIssue);
    }
}
