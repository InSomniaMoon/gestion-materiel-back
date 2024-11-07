<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemOption;
use Illuminate\Http\Request;
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

    public function getIssues(Item $item)
    {
        return response()->json($item->options()->with('optionIssues')->get());
    }
}
