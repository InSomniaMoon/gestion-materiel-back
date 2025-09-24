<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemIssue;
use App\Models\ItemOption;
use App\Models\ItemOptionIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ItemIssueController extends Controller
{
  public function createIssue(Request $request, Item $item)
  {
    $validator = Validator::make($request->all(), [
      'value' => 'required',
      'usable' => 'boolean',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $issue = $item->issues()->create([
      'status' => 'open',
      'value' => trim($request->value),
    ]);

    return response()->json($issue, 201);
  }

  public function getComments(Item $item, ItemIssue $issue)
  {
    return response()->json($issue->comments()->with('author:id,name')->get());
  }

  public function createComment(Request $request, Item $item, ItemIssue $issue)
  {
    $validator = Validator::make($request->all(), [
      'comment' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }
    Log::info('Creating comment', [
      'item_id' => $item->id,
      'issue_id' => $issue->id,
      'user_id' => Auth::user()->id,
    ]);

    $comment = $issue->comments()->create([
      'comment' => $request->comment,
      'user_id' => Auth::user()->id,
    ]);

    return response()->json($comment, 201);
  }

  public function getIssues(Item $item)
  {
    return response()->json($item->issues()->get());
  }

  public function resolveIssue(Request $request, Item $item, ItemIssue $issue)
  {
    if (
      $item->id !== $issue->item_id ||
      $item->structure_id !== (int) $request->input('structure_id')
    ) {
      return response()->json(['error' => 'Forbidden'], status: 403);
    }
    $issue->status = 'resolved';
    $issue->date_resolution = Carbon::now();
    $issue->save();

    return response()->json($issue);
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

    $issues = ItemIssue::where('status', 'open')
      ->with([
        'item:id,name',
        'comments.author:id,name',
      ])

      ->whereHas('item', function ($q) use ($structure_id) {
        $q->where('structure_id', $structure_id);
      })

      ->select([
        'id',
        'status',
        'value',
        'item_id',
        'created_at',
      ])
      ->orderBy('created_at', 'desc')
      ->groupBy(['id'])
      ->paginate($perPage, ['*'], 'page', $page);

    return response()->json($issues);
  }
}
