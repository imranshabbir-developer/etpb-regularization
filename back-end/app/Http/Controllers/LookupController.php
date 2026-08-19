<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cascading geography lookups for the intake forms.
 *
 * The requirements ask for the property's Mouza, City, Tehsil, District and
 * Province. District and province come from one choice; tehsil and mouza depend
 * on it, so they are fetched rather than rendering every tehsil in the country
 * into every page.
 *
 * Where no mouza master exists for a tehsil the form falls back to free text,
 * because a revenue mouza list is district-specific data the Board of Revenue
 * has to supply and an applicant should not be blocked waiting for it.
 */
class LookupController extends Controller
{
    public function tehsils(Request $request): JsonResponse
    {
        $data = $request->validate([
            'district' => ['required', 'integer', 'exists:districts,id'],
        ]);

        $district = DB::table('districts as d')
            ->join('provinces as p', 'p.id', '=', 'd.province_id')
            ->where('d.id', $data['district'])
            ->select('d.id', 'd.name', 'p.name as province')
            ->first();

        return response()->json([
            'province' => $district?->province,
            'tehsils'  => DB::table('tehsils')
                ->where('district_id', $data['district'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function mouzas(Request $request): JsonResponse
    {
        $data = $request->validate([
            'tehsil' => ['required', 'integer', 'exists:tehsils,id'],
        ]);

        return response()->json([
            'mouzas' => DB::table('mouzas')
                ->where('tehsil_id', $data['tehsil'])
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'hadbast_no']),
        ]);
    }
}
