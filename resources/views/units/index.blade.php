@extends('layouts.app')

@section('title', 'Units of Measure')

@section('content')

<div class="top">

    <div>
        <h1>Units of Measure</h1>

        <div class="muted">
            Maintain the units used when construction materials are purchased and sold.
        </div>
    </div>

    <div class="who">
        Owner
    </div>

</div>

<div class="tabs">
    <a href="{{ route('products.index') }}">Products</a>
    <a href="{{ route('categories.index') }}">Categories</a>
    <a class="active" href="{{ route('units.index') }}">Units of Measure</a>
    <a href="{{ route('inventory.index') }}">Inventory</a>
</div>

<div class="units-layout">

    {{-- =====================================================
         ADD UNIT
    ====================================================== --}}

    <div class="card units-form-card">

        <div class="units-card-heading">

            <div>
                <h2>Add Unit</h2>

                <p>
                    Create a unit that can be assigned to construction materials.
                </p>
            </div>

        </div>


        <form
            method="POST"
            action="{{ route('units.store') }}"
            id="addUnitForm"
        >

            @csrf


            {{-- UNIT NAME --}}
            <div class="field">

                <label for="unit_name">
                    Unit Name
                </label>

                <input
                    class="input"
                    id="unit_name"
                    name="unit_name"
                    value="{{ old('unit_name') }}"
                    required
                    maxlength="100"
                    placeholder="e.g. Piece"
                >

            </div>


            {{-- SYMBOL --}}
            <div class="field">

                <label for="unit_symbol">
                    Symbol
                </label>

                <input
                    class="input"
                    id="unit_symbol"
                    name="unit_symbol"
                    value="{{ old('unit_symbol') }}"
                    required
                    maxlength="20"
                    placeholder="e.g. pc"
                >

            </div>


            {{-- UNIT TYPE --}}
            <div class="field">

                <label for="unit_type">
                    Unit Type
                </label>

                <select
                    class="input"
                    id="unit_type"
                    name="unit_type"
                    required
                >

                    <option value="">
                        Select unit type
                    </option>

                    <option
                        value="COUNT"
                        @selected(old('unit_type') === 'COUNT')
                    >
                        Count
                    </option>

                    <option
                        value="LENGTH"
                        @selected(old('unit_type') === 'LENGTH')
                    >
                        Length
                    </option>

                    <option
                        value="WEIGHT"
                        @selected(old('unit_type') === 'WEIGHT')
                    >
                        Weight
                    </option>

                    <option
                        value="VOLUME"
                        @selected(old('unit_type') === 'VOLUME')
                    >
                        Volume
                    </option>

                </select>

            </div>


            <div class="unit-type-note">

                <strong>Examples:</strong>

                Piece and Box use Count,
                Meter and Centimeter use Length,
                Kilogram uses Weight,
                and Cubic Meter can use Volume.

            </div>


            <button
                type="button"
                class="btn primary"
                id="openAddUnitModal"
            >
                Add Unit
            </button>

        </form>

    </div>


    {{-- =====================================================
         AVAILABLE UNITS
    ====================================================== --}}

    <div class="card units-list-card">

        <div class="units-card-heading units-list-heading">

            <div>

                <h2>
                    Available Units
                </h2>

                <p>
                    Active units can be assigned to product units.
                </p>

            </div>


            <div class="unit-count">

                {{ $units->count() }}

                {{ $units->count() === 1
                    ? 'unit'
                    : 'units'
                }}

            </div>

        </div>


        <div class="units-table-wrapper">

            <table class="table units-table">

                <thead>

                    <tr>

                        <th>
                            Name
                        </th>

                        <th>
                            Symbol
                        </th>

                        <th>
                            Type
                        </th>

                        <th>
                            Status
                        </th>

                        <th>
                            Action
                        </th>

                    </tr>

                </thead>


                <tbody>

                    @forelse($units as $unit)

                        <tr>

                            {{-- NAME --}}
                            <td>

                                <strong class="unit-name">
                                    {{ $unit->unit_name }}
                                </strong>

                            </td>


                            {{-- SYMBOL --}}
                            <td>

                                {{ $unit->unit_symbol }}

                            </td>


                            {{-- TYPE --}}
                            <td>

                                <span class="unit-type">

                                    {{ ucfirst(
                                        strtolower(
                                            $unit->unit_type
                                        )
                                    ) }}

                                </span>

                            </td>


                            {{-- STATUS --}}
                            <td>

                                @if($unit->is_active)

                                    <span class="unit-status active">

                                        <span class="status-dot"></span>

                                        Active

                                    </span>

                                @else

                                    <span class="unit-status inactive">

                                        <span class="status-dot"></span>

                                        Archived

                                    </span>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'units.toggle',
                                        $unit
                                    ) }}"
                                    class="unit-toggle-form"
                                    data-name="{{ $unit->unit_name }}"
                                    data-action="{{ $unit->is_active
                                        ? 'archive'
                                        : 'restore'
                                    }}"
                                >

                                    @csrf
                                    @method('PATCH')


                                    <button
                                        type="button"
                                        class="btn small unit-toggle-button
                                            {{ $unit->is_active
                                                ? 'danger'
                                                : 'success'
                                            }}"
                                    >

                                        {{ $unit->is_active
                                            ? 'Archive'
                                            : 'Restore'
                                        }}

                                    </button>

                                </form>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="5"
                                class="units-empty"
                            >

                                No units of measure have been created yet.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


{{-- =========================================================
     ADD UNIT CONFIRMATION MODAL
========================================================= --}}

<div
    class="units-modal-overlay"
    id="addUnitModal"
>

    <div class="units-modal">

        <div class="units-modal-icon add">
            +
        </div>


        <h2>
            Add Unit of Measure?
        </h2>


        <p>
            Please review the unit information before adding it to the system.
        </p>


        <div class="units-modal-details">

            <div>

                <span>
                    Unit Name
                </span>

                <strong id="confirmUnitName">
                    —
                </strong>

            </div>


            <div>

                <span>
                    Symbol
                </span>

                <strong id="confirmUnitSymbol">
                    —
                </strong>

            </div>


            <div>

                <span>
                    Type
                </span>

                <strong id="confirmUnitType">
                    —
                </strong>

            </div>

        </div>


        <div class="units-modal-actions">

            <button
                type="button"
                class="btn light"
                id="cancelAddUnit"
            >
                Cancel
            </button>


            <button
                type="button"
                class="btn primary"
                id="confirmAddUnit"
            >
                Add Unit
            </button>

        </div>

    </div>

</div>


{{-- =========================================================
     ARCHIVE / RESTORE MODAL
========================================================= --}}

<div
    class="units-modal-overlay"
    id="toggleUnitModal"
>

    <div class="units-modal">

        <div
            class="units-modal-icon"
            id="toggleModalIcon"
        >
            !
        </div>


        <h2 id="toggleModalTitle">
            Archive Unit?
        </h2>


        <p id="toggleModalMessage">
            Are you sure you want to continue?
        </p>


        <div class="toggle-unit-name">

            <span>
                Unit
            </span>

            <strong id="toggleUnitName">
                —
            </strong>

        </div>


        <div class="units-modal-actions">

            <button
                type="button"
                class="btn light"
                id="cancelToggleUnit"
            >
                Cancel
            </button>


            <button
                type="button"
                class="btn danger"
                id="confirmToggleUnit"
            >
                Archive Unit
            </button>

        </div>

    </div>

</div>


<style>

/* =========================================================
   LAYOUT
========================================================= */

.units-layout {
    display: grid;

    grid-template-columns:
        minmax(320px, .95fr)
        minmax(500px, 1.25fr);

    gap: 20px;
}


.units-form-card,
.units-list-card {
    margin-bottom: 0;
}


.units-card-heading {
    margin-bottom: 22px;
}


.units-card-heading h2 {
    margin: 0 0 5px;

    font-size: 20px;
}


.units-card-heading p {
    margin: 0;

    color: #7b879d;

    font-size: 11px;

    line-height: 1.5;
}


.units-list-heading {
    display: flex;

    align-items: flex-start;
    justify-content: space-between;

    gap: 15px;
}


.unit-count {
    padding: 6px 10px;

    border-radius: 999px;

    background: #eef2f8;

    color: #69758b;

    font-size: 10px;

    font-weight: 700;

    white-space: nowrap;
}


/* =========================================================
   FORM
========================================================= */

.units-form-card .input {
    height: 41px;

    background: #f5f7fb;

    font-size: 12px;
}


.units-form-card select.input {
    cursor: pointer;
}


.unit-type-note {
    margin:
        -3px
        0
        20px;

    padding: 11px 12px;

    border-radius: 7px;

    background: #f7f9fc;

    color: #7b879d;

    font-size: 10px;

    line-height: 1.6;
}


.unit-type-note strong {
    color: #46536a;
}


/* =========================================================
   TABLE
========================================================= */

.units-table-wrapper {
    width: 100%;

    overflow-x: auto;
}


.units-table {
    min-width: 590px;
}


.units-table th {
    white-space: nowrap;
}


.units-table td {
    vertical-align: middle;
}


.unit-name {
    color: #182033;

    font-weight: 600;
}


.unit-type {
    color: #354052;

    font-size: 11px;
}


/* =========================================================
   STATUS
========================================================= */

.unit-status {
    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 6px 10px;

    border-radius: 999px;

    font-size: 10px;

    font-weight: 700;

    text-transform: uppercase;

    letter-spacing: .15px;
}


.unit-status .status-dot {
    width: 6px;
    height: 6px;

    border-radius: 50%;
}


/* ACTIVE - LIGHT GREEN */

.unit-status.active {
    background: #eaf8ef;

    color: #18733a;
}


.unit-status.active .status-dot {
    background: #22a35a;
}


/* ARCHIVED */

.unit-status.inactive {
    background: #fff0f0;

    color: #b42318;
}


.unit-status.inactive .status-dot {
    background: #dc3545;
}


/* =========================================================
   BUTTONS
========================================================= */

.unit-toggle-button {
    min-width: 72px;
}


/* Archive = Red */

.unit-toggle-button.danger {
    background: #dc3545;

    color: #ffffff;
}


.unit-toggle-button.danger:hover {
    background: #c62f3e;
}


/* Restore = Green */

.unit-toggle-button.success {
    background: #12a957;

    color: #ffffff;
}


.unit-toggle-button.success:hover {
    background: #0f954c;
}


/* =========================================================
   EMPTY
========================================================= */

.units-empty {
    padding: 35px !important;

    text-align: center;

    color: #7b879d;
}


/* =========================================================
   MODAL
========================================================= */

.units-modal-overlay {
    position: fixed;

    inset: 0;

    z-index: 100500;

    display: none;

    align-items: center;
    justify-content: center;

    padding: 20px;

    background: rgba(13, 25, 45, .62);

    backdrop-filter: blur(4px);
}


.units-modal-overlay.show {
    display: flex;
}


.units-modal {
    width: 100%;

    max-width: 430px;

    padding: 30px;

    border-radius: 16px;

    background: #ffffff;

    text-align: center;

    box-shadow:
        0 28px 80px
        rgba(15, 23, 42, .28);
}


.units-modal-icon {
    width: 52px;
    height: 52px;

    display: flex;

    align-items: center;
    justify-content: center;

    margin:
        0
        auto
        18px;

    border-radius: 50%;

    background: #fff0f0;

    color: #dc3545;

    font-size: 21px;

    font-weight: 800;
}


.units-modal-icon.add {
    background: #edf4ff;

    color: #2468ee;
}


.units-modal-icon.restore {
    background: #eaf8ef;

    color: #12a957;
}


.units-modal h2 {
    margin: 0 0 8px;

    color: #182033;

    font-size: 20px;
}


.units-modal > p {
    margin:
        0
        auto
        20px;

    max-width: 340px;

    color: #7b879d;

    font-size: 12px;

    line-height: 1.6;
}


/* =========================================================
   ADD DETAILS
========================================================= */

.units-modal-details {
    display: grid;

    grid-template-columns:
        1fr
        1fr
        1fr;

    gap: 8px;

    margin-bottom: 22px;
}


.units-modal-details > div,
.toggle-unit-name {
    padding: 12px;

    border-radius: 8px;

    background: #f5f7fb;
}


.units-modal-details span,
.toggle-unit-name span {
    display: block;

    margin-bottom: 5px;

    color: #7b879d;

    font-size: 9px;

    text-transform: uppercase;
}


.units-modal-details strong,
.toggle-unit-name strong {
    color: #182033;

    font-size: 12px;
}


.toggle-unit-name {
    margin-bottom: 22px;
}


/* =========================================================
   MODAL ACTIONS
========================================================= */

.units-modal-actions {
    display: flex;

    justify-content: center;

    gap: 10px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 1050px) {

    .units-layout {
        grid-template-columns: 1fr;
    }

}


@media(max-width: 600px) {

    .units-modal-details {
        grid-template-columns: 1fr;
    }


    .units-modal-actions {
        flex-direction: column-reverse;
    }


    .units-modal-actions .btn {
        width: 100%;
    }

}

</style>


@push('scripts')

<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | ADD UNIT MODAL
        |--------------------------------------------------------------------------
        */

        const addForm =
            document.getElementById(
                'addUnitForm'
            );


        const addModal =
            document.getElementById(
                'addUnitModal'
            );


        const openAddButton =
            document.getElementById(
                'openAddUnitModal'
            );


        const cancelAddButton =
            document.getElementById(
                'cancelAddUnit'
            );


        const confirmAddButton =
            document.getElementById(
                'confirmAddUnit'
            );


        openAddButton.addEventListener(
            'click',
            function () {

                if (!addForm.reportValidity()) {
                    return;
                }


                const unitName =
                    document.getElementById(
                        'unit_name'
                    ).value.trim();


                const symbol =
                    document.getElementById(
                        'unit_symbol'
                    ).value.trim();


                const typeSelect =
                    document.getElementById(
                        'unit_type'
                    );


                const typeText =
                    typeSelect.options[
                        typeSelect.selectedIndex
                    ].text;


                document.getElementById(
                    'confirmUnitName'
                ).textContent =
                    unitName;


                document.getElementById(
                    'confirmUnitSymbol'
                ).textContent =
                    symbol;


                document.getElementById(
                    'confirmUnitType'
                ).textContent =
                    typeText;


                addModal.classList.add(
                    'show'
                );


                document.body.style.overflow =
                    'hidden';

            }
        );


        cancelAddButton.addEventListener(
            'click',
            function () {

                closeModal(
                    addModal
                );

            }
        );


        confirmAddButton.addEventListener(
            'click',
            function () {

                confirmAddButton.disabled =
                    true;


                confirmAddButton.textContent =
                    'Adding...';


                addForm.submit();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | ARCHIVE / RESTORE
        |--------------------------------------------------------------------------
        */

        const toggleModal =
            document.getElementById(
                'toggleUnitModal'
            );


        const toggleTitle =
            document.getElementById(
                'toggleModalTitle'
            );


        const toggleMessage =
            document.getElementById(
                'toggleModalMessage'
            );


        const toggleName =
            document.getElementById(
                'toggleUnitName'
            );


        const toggleIcon =
            document.getElementById(
                'toggleModalIcon'
            );


        const cancelToggle =
            document.getElementById(
                'cancelToggleUnit'
            );


        const confirmToggle =
            document.getElementById(
                'confirmToggleUnit'
            );


        let pendingToggleForm =
            null;


        document
            .querySelectorAll(
                '.unit-toggle-button'
            )
            .forEach(function (button) {

                button.addEventListener(
                    'click',
                    function () {

                        const form =
                            this.closest(
                                '.unit-toggle-form'
                            );


                        if (!form) {
                            return;
                        }


                        pendingToggleForm =
                            form;


                        const name =
                            form.dataset.name;


                        const action =
                            form.dataset.action;


                        toggleName.textContent =
                            name;


                        if (action === 'archive') {

                            toggleTitle.textContent =
                                'Archive Unit?';


                            toggleMessage.textContent =
                                'This unit will no longer be available for new product-unit assignments.';


                            confirmToggle.textContent =
                                'Archive Unit';


                            confirmToggle.className =
                                'btn danger';


                            toggleIcon.textContent =
                                '!';


                            toggleIcon.className =
                                'units-modal-icon';

                        } else {

                            toggleTitle.textContent =
                                'Restore Unit?';


                            toggleMessage.textContent =
                                'This unit will become available for product-unit assignments again.';


                            confirmToggle.textContent =
                                'Restore Unit';


                            confirmToggle.className =
                                'btn success';


                            toggleIcon.textContent =
                                '✓';


                            toggleIcon.className =
                                'units-modal-icon restore';

                        }


                        toggleModal.classList.add(
                            'show'
                        );


                        document.body.style.overflow =
                            'hidden';

                    }
                );

            });


        cancelToggle.addEventListener(
            'click',
            function () {

                closeToggleModal();

            }
        );


        confirmToggle.addEventListener(
            'click',
            function () {

                if (!pendingToggleForm) {
                    return;
                }


                confirmToggle.disabled =
                    true;


                pendingToggleForm.submit();

            }
        );


        /*
        |--------------------------------------------------------------------------
        | CLOSE MODALS
        |--------------------------------------------------------------------------
        */

        function closeModal(modal) {

            modal.classList.remove(
                'show'
            );


            document.body.style.overflow =
                '';

        }


        function closeToggleModal() {

            closeModal(
                toggleModal
            );


            pendingToggleForm =
                null;


            confirmToggle.disabled =
                false;

        }


        addModal.addEventListener(
            'click',
            function (event) {

                if (event.target === addModal) {

                    closeModal(
                        addModal
                    );

                }

            }
        );


        toggleModal.addEventListener(
            'click',
            function (event) {

                if (event.target === toggleModal) {

                    closeToggleModal();

                }

            }
        );


        document.addEventListener(
            'keydown',
            function (event) {

                if (event.key !== 'Escape') {
                    return;
                }


                if (
                    addModal.classList.contains(
                        'show'
                    )
                ) {

                    closeModal(
                        addModal
                    );

                }


                if (
                    toggleModal.classList.contains(
                        'show'
                    )
                ) {

                    closeToggleModal();

                }

            }
        );

    }
);

</script>

@endpush

@endsection