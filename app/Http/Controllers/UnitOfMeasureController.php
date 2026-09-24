<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\UnitOfMeasure;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitOfMeasureController extends Controller
{
    /**
     * Display all units of measure.
     */
    public function index()
    {
        $units = UnitOfMeasure::orderBy('unit_name')
            ->get();

        return view('units.index', compact('units'));
    }

    /**
     * Store a new unit of measure.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_name' => [
                'required',
                'string',
                'max:100',
                'unique:units_of_measure,unit_name',
            ],

            'unit_symbol' => [
                'required',
                'string',
                'max:20',
                'unique:units_of_measure,unit_symbol',
            ],

            'unit_type' => [
                'required',
                Rule::in([
                    'COUNT',
                    'LENGTH',
                    'WEIGHT',
                    'VOLUME',
                ]),
            ],
        ]);

        $unit = UnitOfMeasure::create([
            'unit_name' => trim($validated['unit_name']),
            'unit_symbol' => trim($validated['unit_symbol']),
            'unit_type' => $validated['unit_type'],
            'is_active' => true,
        ]);

        $this->log(
            $unit,
            'CREATE',
            "Created unit of measure '{$unit->unit_name}' ({$unit->unit_symbol})."
        );

        return redirect()
            ->route('units.index')
            ->with(
                'success',
                'Unit of measure added successfully.'
            );
    }

    /**
     * Update an existing unit.
     */
    public function update(
        Request $request,
        UnitOfMeasure $unit
    ) {
        $validated = $request->validate([
            'unit_name' => [
                'required',
                'string',
                'max:100',

                Rule::unique(
                    'units_of_measure',
                    'unit_name'
                )->ignore(
                    $unit->unit_id,
                    'unit_id'
                ),
            ],

            'unit_symbol' => [
                'required',
                'string',
                'max:20',

                Rule::unique(
                    'units_of_measure',
                    'unit_symbol'
                )->ignore(
                    $unit->unit_id,
                    'unit_id'
                ),
            ],

            'unit_type' => [
                'required',

                Rule::in([
                    'COUNT',
                    'LENGTH',
                    'WEIGHT',
                    'VOLUME',
                ]),
            ],
        ]);

        $oldName = $unit->unit_name;

        $unit->update([
            'unit_name' => trim($validated['unit_name']),
            'unit_symbol' => trim($validated['unit_symbol']),
            'unit_type' => $validated['unit_type'],
        ]);

        $this->log(
            $unit,
            'UPDATE',
            "Updated unit of measure '{$oldName}' to '{$unit->unit_name}'."
        );

        return redirect()
            ->route('units.index')
            ->with(
                'success',
                'Unit of measure updated successfully.'
            );
    }

    /**
     * Archive or restore a unit.
     */
    public function toggle(UnitOfMeasure $unit)
    {
        $newStatus = !$unit->is_active;

        $unit->update([
            'is_active' => $newStatus,
        ]);

        $actionText = $newStatus
            ? 'Restored'
            : 'Archived';

        $this->log(
            $unit,
            'UPDATE',
            "{$actionText} unit of measure '{$unit->unit_name}'."
        );

        return redirect()
            ->route('units.index')
            ->with(
                'success',
                "Unit of measure {$actionText} successfully."
            );
    }

    /**
     * Create activity log.
     */
    private function log(
        UnitOfMeasure $unit,
        string $action,
        string $description
    ) {
        ActivityLog::create([
            'user_id' => auth()->id(),

            'module' => 'UNIT',

            'action' => $action,

            'description' => $description,

            'reference_type' => 'UnitOfMeasure',

            'reference_id' => $unit->unit_id,

            'created_at' => now(),
        ]);
    }
}