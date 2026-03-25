<?php

namespace App\Http\Controllers\web\backend;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Planfeature;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;

class PlanfeatureController extends Controller
{
    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $features = Planfeature::with('plan')->select('planfeatures.*');

                return DataTables::of($features)
                    ->addIndexColumn()
                    ->addColumn('plan_name', function($row){
                        return $row->plan->name ?? 'N/A';
                    })
                    ->addColumn('action', function ($row) {
                        $editUrl = route('planfeatures.edit', $row->id);
                        $deleteUrl = route('planfeatures.destroy', $row->id);

                        return '
                        <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
                            <a href="' . $editUrl . '" class="btn btn-primary fs-14 text-white" title="Edit">
                                <i class="fe fe-edit"></i>
                            </a>
                            <button type="button" onclick="confirmDelete(' . $row->id . ')" class="btn btn-danger fs-14 text-white" title="Delete">
                                <i class="fe fe-trash"></i>
                            </button>
                            <form id="delete-form-' . $row->id . '" action="' . $deleteUrl . '" method="POST" style="display: none;">
                                ' . csrf_field() . method_field('DELETE') . '
                            </form>
                        </div>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            $plans = Plan::all();
            return view('backend.layouts.planfeatures.index', compact('plans'));
        } catch (Exception $e) {
            Log::error('Planfeature index failed: ' . $e->getMessage());
            return redirect()->back()->withErrors('Failed to load plan features: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'feature' => 'required',
        ]);

        try {
            Planfeature::create([
                'plan_id' => $request->plan_id,
                'feature' => $request->feature,
            ]);

            return redirect()->route('planfeatures.index')->with('success', 'Plan feature created successfully.');
        } catch (Exception $e) {
            Log::error('Planfeature creation failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors('Failed to create plan feature: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $planfeature = Planfeature::findOrFail($id);
            $plans = Plan::all();
            return view('backend.layouts.planfeatures.edit', compact('planfeature', 'plans'));
        } catch (Exception $e) {
            Log::error('Planfeature edit failed: ' . $e->getMessage());
            return redirect()->route('planfeatures.index')->withErrors('Failed to load plan feature for editing.');
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'feature' => 'required',
        ]);

        try {
            $planfeature = Planfeature::findOrFail($id);
            $planfeature->update([
                'plan_id' => $request->plan_id,
                'feature' => $request->feature,
            ]);

            return redirect()->route('planfeatures.index')->with('success', 'Plan feature updated successfully.');
        } catch (Exception $e) {
            Log::error('Planfeature update failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors('Failed to update plan feature: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $planfeature = Planfeature::findOrFail($id);
            $planfeature->delete();

            return redirect()->route('planfeatures.index')->with('success', 'Plan feature deleted successfully.');
        } catch (Exception $e) {
            Log::error('Planfeature deletion failed: ' . $e->getMessage());
            return redirect()->back()->withErrors('Failed to delete plan feature: ' . $e->getMessage());
        }
    }
}
