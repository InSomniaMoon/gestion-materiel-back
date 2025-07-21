<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemOption;
use App\Models\ItemOptionIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ItemOptionIssueController extends Controller
{
  public function createIssue(Request $request, Item $item, ItemOption $option)
  {
    $validator = Validator::make($request->all(), [
      'value' => 'required',
      'usable' => 'boolean',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $issue = $option->optionIssues()->create([
      'status' => 'open',
      'value' => trim($request->value),
      'item_option_id' => $option->id,
    ]);

    if ($option->usable) {
      $option->usable = $request->usable;
      $option->save();
    }

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
    return response()->json($item->options()->with([
      'optionIssues' => function ($q) {
        $q->where('status', 'open');
      },
    ])->get());
  }

  public function resolveIssue(ItemOption $option, ItemOptionIssue $optionIssue)
  {
    $optionIssue->status = 'resolved';
    $optionIssue->save();

    return response()->json($optionIssue);
  }

  public function getIssuesForItem(Request $request, Item $item)
  {
    $item->load('options.optionIssues');

    $item->options()->each(function ($option) {
      $option->optionIssues->each(function (ItemOptionIssue $issue) {
        Log::info('Item Option Issue', [
          'created_at' => $issue->created_at,
          'created_at_carbon' => Carbon::parse($issue->created_at),
        ]);
      });
    });

    Log::info("Item Option Issues for Item: $item->id", [
      'issues' => $item->options,
    ]);

    return response()->json($item->options);
  }

  public function getPaginatedOpenedIssues(Request $request)
  {
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);
    $group_id = $request->input('group_id');

    $issues = ItemOptionIssue::where('status', 'open')
      ->with([
        'itemOption:id,name,usable,item_id',
        'itemOption.item:id,name',
        'comments.author:id,name',
      ])
      ->whereHas('itemOption', function ($query) use ($group_id) {
        $query->whereHas('item', function ($q) use ($group_id) {
          $q->where('group_id', $group_id);
        });
      })
      ->select([
        'id',
        'status',
        'value',
        'item_option_id',
        'created_at',
      ])
      ->orderBy('created_at', 'desc')
      ->groupBy(['id'])
      ->paginate($perPage, ['*'], 'page', $page);

    return response()->json($issues);
  }
}
