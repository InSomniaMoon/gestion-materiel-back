<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class ItemIssueController extends Controller
{
  public function createIssue(Request $request, Item $item)
  {
    $validator = Validator::make($request->all(), [
      'value' => 'required',
      'usable' => 'boolean',
      'affected_quantity' => "required|integer|min:1|max:$item->stock",
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $issue = $item->issues()->create([
      'status' => 'open',
      'value' => trim($request->value),
      'reported_by' => Auth::user()->id,
    ]);

    if (! $item->category->identified) {
      $issue->update([
        'affected_quantity' => $request->affected_quantity,
      ]);
    }

    return response()->json($issue, 201);
  }

  public function getComments(Item $item, ItemIssue $issue)
  {
    return response()->json($issue->comments()->with('author:id,lastname,firstname')->get());
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
    return response()->json($item->issues()->where('status', 'open')->get());
  }

  public function resolveIssue(Request $request, Item $item, ItemIssue $issue)
  {
    // structure mask from jwt
    $payload = JWTAuth::parseToken()->getPayload();
    $code_structure_mask = $payload->get('selected_structure.mask');

    if (
      $item->id !== $issue->item_id ||
      strncmp($item->structure()->get()[0]->code_structure, $code_structure_mask, strlen($code_structure_mask)) !== 0
    ) {
      Log::warning('Unauthorized attempt to resolve issue', [
        'item_id' => $item->id,
        'issue_id' => $issue->id,
        'issue.item_id' => $issue->item_id,
        'structure_id' => $request->input('structure_id'),
      ]);

      return response()->json(['error' => 'L\'item ne correspond pas à la structure spécifiée ou au problème spécifié.'], status: 403);
    }
    $issue->status = 'resolved';
    $issue->resolution_date = Carbon::now();
    $issue->save();

    return response()->json($issue);
  }

  public function getPaginatedOpenedIssues(Request $request)
  {
    $perPage = $request->input('per_page', 10);
    $page = $request->input('page', 1);
    $structure_id = $request->input('structure_id');

    $issues = ItemIssue::where('status', 'open')
      ->with([
        'item:id,name',
        'comments.author:id,lastname,firstname',
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
