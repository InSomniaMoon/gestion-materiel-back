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

  public function getComments(Item $item, ItemOption $option, ItemOptionIssue $optionIssue)
  {
    return response()->json($optionIssue->comments()->with('author:id,name')->get());
  }

  public function createComment(Request $request, Item $item, ItemOption $option, ItemOptionIssue $optionIssue)
  {
    $validator = Validator::make($request->all(), [
      'comment' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }
    Log::info('Creating comment', [
      'item_id' => $item->id,
      'option_id' => $option->id,
      'issue_id' => $optionIssue->id,
      'user_id' => Auth::user()->id,
    ]);

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

  public function resolveIssue(Request $request, Item $item, ItemOption $option, ItemOptionIssue $optionIssue)
  {
    if (
      $item->id !== $option->item_id ||
      $option->id !== $optionIssue->item_option_id ||
      $item->structure_id !== (int) $request->input('structure_id')
    ) {
      return response()->json(['error' => 'Forbidden'], status: 403);
    }
    $optionIssue->status = 'resolved';
    $optionIssue->date_resolution = Carbon::now();
    $optionIssue->save();

    return response()->json($optionIssue);
  }

  public function getOptionsWithIssues(Request $request, Item $item)
  {
    $item->load([
      'options.optionIssues' => function ($q) {
        $q->where('status', 'open');
      },
    ]);

    Log::info("Item Option Issues for Item: $item->id", [
      'issues' => $item->options,
    ]);

    return response()->json($item->options);
  }

  public function getPaginatedOpenedIssues(Request $request)
  {
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);
    $structure_id = $request->input('structure_id');

    $issues = ItemOptionIssue::where('status', 'open')
      ->with([
        'itemOption:id,name,usable,item_id',
        'itemOption.item:id,name',
        'comments.author:id,name',
      ])
      ->whereHas('itemOption', function ($query) use ($structure_id) {
        $query->whereHas('item', function ($q) use ($structure_id) {
          $q->where('structure_id', $structure_id);
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
