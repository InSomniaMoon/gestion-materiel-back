<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use App\Models\StructureType;
use Illuminate\Http\Request;
use Log;

class StructureController extends Controller
{
  public function getStructuresWithMembers(Request $request)
  {
    $structure_id = $request->query('structure_id');

    $structure = Structure::find($structure_id);

    if ($structure->type == Structure::GROUPE) {
      // slice 2 last characters
      $base_code = substr($structure->code_structure, 0, -2);
      $children = Structure::with('members:id,firstname,lastname')
        ->where('code_structure', 'LIKE', "$base_code%")
        ->orderBy('code_structure')
        ->get();

      return compact(['structure', 'children']);
    }

    return [$structure];
  }
}
