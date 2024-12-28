<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemOption;
use App\Models\ItemOptionIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ItemOptionIssueController extends Controller
{
  public function createIssue(Request $request, Item $item, ItemOption $option)
  {
    $validator = Validator::make($request->all(), [
      'value' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $issue = $option->optionIssues()->create([
      'status' => 'open',
      'value' => trim($request->value),
      'item_option_id' => $option->id,
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

  public function getIssuesForItems()
  {
    // item_ids from query params
    $itemIds = request()->query('item_ids');
    Log::info($itemIds);
    if (! $itemIds) {
      return response()->json([]);
    }
    // item_ids is a string of comma separated integers
    $itemIds = explode(',', $itemIds);

    return response()->json(ItemOptionIssue::where('status', 'open')
        ->where(function ($q) use ($itemIds) {
          $q->whereIn('item_option_id', function ($q) use ($itemIds) {
            $q->select('id')->from('item_options')->whereIn('item_id', $itemIds);
          });
        })
        ->with('itemOption.item')->get());
  }
}
