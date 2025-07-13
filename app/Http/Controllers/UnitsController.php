<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnitsController extends Controller
{
  public function getUnits()
  {
    $units = Unit::where('group_id', request()->query('group_id'))
      ->with(['responsible:id,name'])
      ->with('chiefs:id,name')
      ->get();

    return response()->json($units);
  }

  public function createUnit(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'required|string|max:255',
      'group_id' => 'required|exists:groups,id',
      'color' => 'nullable|string|min:7|max:7', // Assuming color is a hex code
      'responsible' => 'nullable|exists:users,id',
      'chiefs' => 'nullable|array',
      'chiefs.*' => 'exists:users,id',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $unit = Unit::create([
      'name' => $request->input('name'),
      'group_id' => $request->input('group_id'),
      'color' => $request->input('color'),
      'responsible_id' => $request->input('responsible'),
    ]);

    if ($request->has('chiefs')) {
      $unit->chiefs()->sync($request->input('chiefs'));
    }

    return response()->json($unit, 201);
  }

  public function updateUnit(Request $request, $id)
  {
    $validator = Validator::make($request->all(), [
      'name' => 'nullable|string|max:255',
      'color' => 'nullable|string|min:7|max:7', // Assuming color is a hex code
      'responsible' => 'nullable|exists:users,id',
      'chiefs' => 'nullable|array',
      'chiefs.*' => 'exists:users,id',
    ]);

    if ($validator->fails()) {
      return response()->json($validator->errors(), 400);
    }

    $unit = Unit::findOrFail($id);

    $unit->chiefs()->sync($request->input('chiefs', []));

    $unit->update([
      'name' => $request->input('name'),
      'color' => $request->input('color'),
      'responsible_id' => $request->input('responsible'),
    ]);

    return response()->json($unit, 200);
  }
}
