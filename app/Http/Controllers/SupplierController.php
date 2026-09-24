<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display supplier records.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));
        $status = $request->get('status', 'all');

        $suppliers = Supplier::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('supplier_name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->orderBy('supplier_name')
            ->paginate(10)
            ->withQueryString();

        return view('suppliers.index', compact(
            'suppliers',
            'search',
            'status'
        ));
    }

    /**
     * Kept for compatibility with resource routes.
     * Adding is now handled by a modal.
     */
    public function create()
    {
        return redirect()->route('suppliers.index');
    }

    /**
     * Store a supplier.
     */
    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $validated['supplier_name'] =
            trim($validated['supplier_name']);

        $validated['contact_person'] =
            !empty($validated['contact_person'])
                ? trim($validated['contact_person'])
                : null;

        $validated['contact_number'] =
            !empty($validated['contact_number'])
                ? trim($validated['contact_number'])
                : null;

        $validated['email'] =
            !empty($validated['email'])
                ? trim($validated['email'])
                : null;

        $validated['address'] =
            !empty($validated['address'])
                ? trim($validated['address'])
                : null;

        $validated['is_active'] = true;

        $supplier = Supplier::create($validated);

        $this->log(
            'CREATE',
            "Created supplier '{$supplier->supplier_name}'.",
            $supplier
        );

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier added successfully.');
    }

    /**
     * Kept for compatibility with resource routes.
     * Editing is now handled by a modal.
     */
    public function edit(Supplier $supplier)
    {
        return redirect()
            ->route('suppliers.index', [
                'edit' => $supplier->supplier_id,
            ]);
    }

    /**
     * Update supplier.
     */
    public function update(
        Request $request,
        Supplier $supplier
    ) {
        $validated = $this->validateData($request);

        $validated['supplier_name'] =
            trim($validated['supplier_name']);

        $validated['contact_person'] =
            !empty($validated['contact_person'])
                ? trim($validated['contact_person'])
                : null;

        $validated['contact_number'] =
            !empty($validated['contact_number'])
                ? trim($validated['contact_number'])
                : null;

        $validated['email'] =
            !empty($validated['email'])
                ? trim($validated['email'])
                : null;

        $validated['address'] =
            !empty($validated['address'])
                ? trim($validated['address'])
                : null;

        $oldName = $supplier->supplier_name;

        $supplier->update($validated);

        $this->log(
            'UPDATE',
            "Updated supplier '{$oldName}' to '{$supplier->supplier_name}'.",
            $supplier
        );

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    /**
     * Archive or restore supplier.
     */
    public function toggle(Supplier $supplier)
    {
        $supplier->update([
            'is_active' => !$supplier->is_active,
        ]);

        $action = $supplier->is_active
            ? 'RESTORE'
            : 'ARCHIVE';

        $description = $supplier->is_active
            ? "Restored supplier '{$supplier->supplier_name}'."
            : "Archived supplier '{$supplier->supplier_name}'.";

        $this->log(
            $action,
            $description,
            $supplier
        );

        return back()->with(
            'success',
            $supplier->is_active
                ? 'Supplier restored successfully.'
                : 'Supplier archived successfully.'
        );
    }

    /**
     * Supplier validation.
     */
    private function validateData(Request $request): array
    {
        return $request->validate([
            'supplier_name' => [
                'required',
                'string',
                'max:150',
            ],

            'contact_person' => [
                'nullable',
                'string',
                'max:150',
            ],

            'contact_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'email' => [
                'nullable',
                'email',
                'max:150',
            ],

            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);
    }

    /**
     * Save activity log.
     */
    private function log(
        string $action,
        string $description,
        Supplier $supplier
    ): void {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => 'SUPPLIER',
            'action' => $action,
            'description' => $description,
            'reference_type' => 'Supplier',
            'reference_id' => $supplier->supplier_id,
        ]);
    }
}