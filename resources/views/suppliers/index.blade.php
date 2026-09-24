@extends('layouts.app')

@section('title', 'Supplier Management')

@section('content')

<style>
    /* =========================================================
       SUPPLIER MANAGEMENT
       ========================================================= */

    .supplier-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .supplier-filters {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    /* =========================================================
       SEARCH - SAME STYLE AS INVENTORY
       ========================================================= */

    .supplier-search {
        position: relative;
        width: 280px;
    }

    .supplier-search-icon {
        position: absolute;
        left: 13px;
        top: 50%;
        transform: translateY(-50%);
        width: 14px;
        height: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        z-index: 2;
    }

    .supplier-search-icon svg {
        display: block;
        width: 14px;
        height: 14px;
        stroke: #8b98ad;
    }

    .supplier-search .input {
        width: 100%;
        height: 40px;
        padding: 11px 14px 11px 38px;
        border: 1px solid #dfe5ee;
        border-radius: 7px;
        background: #fff;
        color: #182033;
        font-family: Inter, "Segoe UI", Arial, sans-serif;
        font-size: 12px;
        font-weight: 400;
        outline: none;
    }

    .supplier-search .input::placeholder {
        color: #7b879d;
        opacity: 1;
    }

    .supplier-search .input:focus {
        border-color: #2468ee;
        box-shadow: none;
    }

    /* =========================================================
       FILTER
       ========================================================= */

    .supplier-status-select {
        width: 150px;
        height: 40px;
        padding: 0 12px;
        border: 1px solid #dfe5ee;
        border-radius: 7px;
        background: #fff;
        color: #182033;
        font-family: Inter, "Segoe UI", Arial, sans-serif;
        font-size: 12px;
        font-weight: 400;
        outline: none;
    }

    .supplier-status-select:focus {
        border-color: #2468ee;
    }

    .supplier-filter-button {
        height: 40px;
        padding-top: 0;
        padding-bottom: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    /* =========================================================
       TABLE
       ========================================================= */

    .supplier-table-container {
        width: 100%;
        overflow-x: auto;
    }

    .supplier-name {
        font-weight: 700;
        color: #182033;
    }

    /* =========================================================
       STATUS BADGES
       ========================================================= */

    .supplier-status {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 60px;
        padding: 5px 9px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
    }

    .supplier-status.active {
        background: #eaf8ef;
        color: #18733a;
    }

    .supplier-status.archived {
        background: #fff0f0;
        color: #b42318;
    }

    /* =========================================================
       MODALS
       ========================================================= */

    .supplier-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;

        display: none;
        align-items: center;
        justify-content: center;

        padding: 20px;

        background: rgba(13, 25, 45, 0.55);
    }

    .supplier-modal-overlay.show {
        display: flex;
    }

    .supplier-modal {
        width: 100%;
        max-width: 650px;

        background: #fff;
        border-radius: 12px;

        box-shadow:
            0 20px 50px rgba(13, 25, 45, 0.20);

        overflow: hidden;
    }

    .supplier-confirm-modal {
        max-width: 430px;
    }

    .supplier-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 15px;

        padding: 22px 24px 16px;

        border-bottom: 1px solid #edf0f5;
    }

    .supplier-modal-header h2 {
        margin: 0 0 5px;
        font-size: 21px;
    }

    .supplier-modal-close {
        width: 32px;
        height: 32px;

        display: flex;
        align-items: center;
        justify-content: center;

        border: 0;
        border-radius: 7px;

        background: #eef2f8;
        color: #46536a;

        font-size: 20px;

        cursor: pointer;
    }

    .supplier-modal-close:hover {
        background: #e1e7f0;
    }

    .supplier-modal-body {
        padding: 22px 24px;
    }

    .supplier-modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 8px;

        padding: 0 24px 22px;
    }

    /* =========================================================
       MODAL FORM
       ========================================================= */

    .supplier-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .supplier-form-grid .full {
        grid-column: 1 / -1;
    }

    .supplier-form-grid textarea {
        min-height: 95px;
        resize: vertical;
    }

    /* =========================================================
       PAGINATION
       ========================================================= */

    .supplier-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 15px;

        margin-top: 18px;

        flex-wrap: wrap;
    }

    .supplier-pagination-links {
        display: flex;
        align-items: center;
        gap: 5px;

        flex-wrap: wrap;
    }

    .supplier-page-link {
        min-width: 34px;
        height: 34px;

        display: inline-flex;
        align-items: center;
        justify-content: center;

        padding: 0 10px;

        border: 1px solid #dfe5ee;
        border-radius: 6px;

        background: #fff;
        color: #354052;

        font-size: 12px;
        text-decoration: none;
    }

    .supplier-page-link:hover {
        background: #eef2f8;
    }

    .supplier-page-link.active {
        background: #2468ee;
        border-color: #2468ee;
        color: #fff;
        font-weight: 700;
    }

    .supplier-page-link.disabled {
        opacity: 0.45;
        pointer-events: none;
    }

    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media (max-width: 800px) {

        .supplier-toolbar {
            align-items: stretch;
        }

        .supplier-filters {
            width: 100%;
        }

        .supplier-search {
            width: 100%;
        }

        .supplier-status-select {
            flex: 1;
        }

        .supplier-form-grid {
            grid-template-columns: 1fr;
        }

        .supplier-form-grid .full {
            grid-column: auto;
        }
    }
</style>


{{-- =========================================================
     PAGE HEADER
     ========================================================= --}}

<div class="top">

    <div>

        <h1>Supplier Management</h1>

        <div class="muted">
            Maintain supplier records used in purchasing
        </div>

    </div>

    <div class="who">
        Owner
    </div>

</div>

<div class="tabs">
    <a class="active" href="{{ route('suppliers.index') }}">Suppliers</a>
    <a href="{{ route('purchases.index') }}">Purchases</a>
</div>

{{-- =========================================================
     TOOLBAR
     ========================================================= --}}

<div class="supplier-toolbar">

    <form
        method="GET"
        action="{{ route('suppliers.index') }}"
        class="supplier-filters"
    >

        {{-- SEARCH --}}
        <div class="supplier-search">

            <span class="supplier-search-icon">

                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                >

                    <circle
                        cx="11"
                        cy="11"
                        r="7"
                        stroke-width="2"
                    ></circle>

                    <path
                        d="M20 20L16.65 16.65"
                        stroke-width="2"
                        stroke-linecap="round"
                    ></path>

                </svg>

            </span>

            <input
                type="text"
                name="search"
                class="input"
                value="{{ $search ?? '' }}"
                placeholder="Search suppliers..."
                autocomplete="off"
            >

        </div>


        {{-- STATUS FILTER --}}
        <select
            name="status"
            class="supplier-status-select"
        >

            <option
                value="all"
                {{ ($status ?? 'all') === 'all' ? 'selected' : '' }}
            >
                All Status
            </option>

            <option
                value="active"
                {{ ($status ?? '') === 'active' ? 'selected' : '' }}
            >
                Active
            </option>

            <option
                value="inactive"
                {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}
            >
                Archived
            </option>

        </select>


        {{-- FILTER BUTTON --}}
        <button
            type="submit"
            class="btn primary supplier-filter-button"
        >
            Filter
        </button>


        {{-- RESET --}}
        @if(
            !empty($search) ||
            (($status ?? 'all') !== 'all')
        )

            <a
                href="{{ route('suppliers.index') }}"
                class="btn light supplier-filter-button"
            >
                Reset
            </a>

        @endif

    </form>


    {{-- ADD SUPPLIER --}}
    <button
        type="button"
        class="btn primary"
        onclick="openAddSupplierModal()"
    >
        + Add Supplier
    </button>

</div>


{{-- =========================================================
     SUPPLIER TABLE
     ========================================================= --}}

<div class="supplier-table-container">

    <table class="table">

        <thead>

            <tr>

                <th>SUPPLIER</th>

                <th>CONTACT PERSON</th>

                <th>PHONE</th>

                <th>EMAIL</th>

                <th>STATUS</th>

                <th>ACTION</th>

            </tr>

        </thead>


        <tbody>

            @forelse($suppliers as $supplier)

                <tr>

                    <td>

                        <span class="supplier-name">
                            {{ $supplier->supplier_name }}
                        </span>

                    </td>


                    <td>
                        {{ $supplier->contact_person ?: '—' }}
                    </td>


                    <td>
                        {{ $supplier->contact_number ?: '—' }}
                    </td>


                    <td>
                        {{ $supplier->email ?: '—' }}
                    </td>


                    <td>

                        @if($supplier->is_active)

                            <span class="supplier-status active">
                                ACTIVE
                            </span>

                        @else

                            <span class="supplier-status archived">
                                ARCHIVED
                            </span>

                        @endif

                    </td>


                    <td>

                        <div class="actions">

                            {{-- EDIT --}}
                            <button
                                type="button"
                                class="btn light small edit-supplier-button"

                                data-id="{{ $supplier->supplier_id }}"

                                data-name="{{ $supplier->supplier_name }}"

                                data-contact-person="{{ $supplier->contact_person ?? '' }}"

                                data-contact-number="{{ $supplier->contact_number ?? '' }}"

                                data-email="{{ $supplier->email ?? '' }}"

                                data-address="{{ $supplier->address ?? '' }}"
                            >
                                Edit
                            </button>


                            {{-- ARCHIVE / RESTORE --}}
                            <button
                                type="button"

                                class="btn {{ $supplier->is_active ? 'danger' : 'success' }} small supplier-toggle-button"

                                data-id="{{ $supplier->supplier_id }}"

                                data-name="{{ $supplier->supplier_name }}"

                                data-active="{{ $supplier->is_active ? '1' : '0' }}"
                            >

                                {{ $supplier->is_active ? 'Archive' : 'Restore' }}

                            </button>

                        </div>

                    </td>

                </tr>


            @empty

                <tr>

                    <td
                        colspan="6"
                        style="
                            text-align:center;
                            padding:35px;
                        "
                    >

                        <div class="muted">
                            No suppliers found.
                        </div>

                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>


{{-- =========================================================
     PAGINATION
     ========================================================= --}}

@if(method_exists($suppliers, 'hasPages') && $suppliers->hasPages())

    <div class="supplier-pagination">

        <div class="muted">

            Showing

            {{ $suppliers->firstItem() }}

            to

            {{ $suppliers->lastItem() }}

            of

            {{ $suppliers->total() }}

            suppliers

        </div>


        <div class="supplier-pagination-links">

            {{-- PREVIOUS --}}
            @if($suppliers->onFirstPage())

                <span class="supplier-page-link disabled">
                    Previous
                </span>

            @else

                <a
                    href="{{ $suppliers->previousPageUrl() }}"
                    class="supplier-page-link"
                >
                    Previous
                </a>

            @endif


            {{-- PAGE NUMBERS --}}
            @for(
                $page = 1;
                $page <= $suppliers->lastPage();
                $page++
            )

                <a
                    href="{{ $suppliers->url($page) }}"
                    class="supplier-page-link {{ $page === $suppliers->currentPage() ? 'active' : '' }}"
                >
                    {{ $page }}
                </a>

            @endfor


            {{-- NEXT --}}
            @if($suppliers->hasMorePages())

                <a
                    href="{{ $suppliers->nextPageUrl() }}"
                    class="supplier-page-link"
                >
                    Next
                </a>

            @else

                <span class="supplier-page-link disabled">
                    Next
                </span>

            @endif

        </div>

    </div>

@endif


{{-- =========================================================
     ADD SUPPLIER MODAL
     ========================================================= --}}

<div
    id="addSupplierModal"
    class="supplier-modal-overlay"
>

    <div class="supplier-modal">

        <div class="supplier-modal-header">

            <div>

                <h2>
                    Add Supplier
                </h2>

                <div class="muted">
                    Add a supplier used for purchasing construction materials.
                </div>

            </div>


            <button
                type="button"
                class="supplier-modal-close"
                onclick="closeAddSupplierModal()"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            action="{{ route('suppliers.store') }}"
        >

            @csrf


            <div class="supplier-modal-body">

                <div class="supplier-form-grid">

                    {{-- NAME --}}
                    <div class="field">

                        <label>
                            Supplier Name
                        </label>

                        <input
                            type="text"
                            name="supplier_name"
                            class="input"
                            value="{{ old('supplier_name') }}"
                            placeholder="Enter supplier name"
                            maxlength="150"
                            required
                        >

                    </div>


                    {{-- CONTACT PERSON --}}
                    <div class="field">

                        <label>
                            Contact Person
                        </label>

                        <input
                            type="text"
                            name="contact_person"
                            class="input"
                            value="{{ old('contact_person') }}"
                            placeholder="Enter contact person"
                            maxlength="150"
                        >

                    </div>


                    {{-- PHONE --}}
                    <div class="field">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            name="contact_number"
                            class="input"
                            value="{{ old('contact_number') }}"
                            placeholder="Enter contact number"
                            maxlength="50"
                        >

                    </div>


                    {{-- EMAIL --}}
                    <div class="field">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="input"
                            value="{{ old('email') }}"
                            placeholder="Enter email address"
                            maxlength="150"
                        >

                    </div>


                    {{-- ADDRESS --}}
                    <div class="field full">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="address"
                            class="input"
                            placeholder="Enter supplier address"
                            maxlength="1000"
                        >{{ old('address') }}</textarea>

                    </div>

                </div>

            </div>


            <div class="supplier-modal-footer">

                <button
                    type="button"
                    class="btn light"
                    onclick="closeAddSupplierModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn primary"
                >
                    Add Supplier
                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     EDIT SUPPLIER MODAL
     ========================================================= --}}

<div
    id="editSupplierModal"
    class="supplier-modal-overlay"
>

    <div class="supplier-modal">

        <div class="supplier-modal-header">

            <div>

                <h2>
                    Edit Supplier
                </h2>

                <div class="muted">
                    Update the selected supplier information.
                </div>

            </div>


            <button
                type="button"
                class="supplier-modal-close"
                onclick="closeEditSupplierModal()"
            >
                &times;
            </button>

        </div>


        <form
            method="POST"
            id="editSupplierForm"
            action=""
        >

            @csrf
            @method('PUT')


            <div class="supplier-modal-body">

                <div class="supplier-form-grid">

                    {{-- NAME --}}
                    <div class="field">

                        <label>
                            Supplier Name
                        </label>

                        <input
                            type="text"
                            name="supplier_name"
                            id="editSupplierName"
                            class="input"
                            maxlength="150"
                            required
                        >

                    </div>


                    {{-- CONTACT --}}
                    <div class="field">

                        <label>
                            Contact Person
                        </label>

                        <input
                            type="text"
                            name="contact_person"
                            id="editSupplierContactPerson"
                            class="input"
                            maxlength="150"
                        >

                    </div>


                    {{-- PHONE --}}
                    <div class="field">

                        <label>
                            Phone
                        </label>

                        <input
                            type="text"
                            name="contact_number"
                            id="editSupplierContactNumber"
                            class="input"
                            maxlength="50"
                        >

                    </div>


                    {{-- EMAIL --}}
                    <div class="field">

                        <label>
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            id="editSupplierEmail"
                            class="input"
                            maxlength="150"
                        >

                    </div>


                    {{-- ADDRESS --}}
                    <div class="field full">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="address"
                            id="editSupplierAddress"
                            class="input"
                            maxlength="1000"
                        ></textarea>

                    </div>

                </div>

            </div>


            <div class="supplier-modal-footer">

                <button
                    type="button"
                    class="btn light"
                    onclick="closeEditSupplierModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    class="btn primary"
                >
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     ARCHIVE / RESTORE CONFIRMATION
     ========================================================= --}}

<div
    id="supplierStatusModal"
    class="supplier-modal-overlay"
>

    <div class="supplier-modal supplier-confirm-modal">

        <div class="supplier-modal-header">

            <div>

                <h2 id="supplierStatusTitle">
                    Confirm Action
                </h2>

                <div
                    id="supplierStatusSubtitle"
                    class="muted"
                >
                    Please confirm this action.
                </div>

            </div>


            <button
                type="button"
                class="supplier-modal-close"
                onclick="closeSupplierStatusModal()"
            >
                &times;
            </button>

        </div>


        <div class="supplier-modal-body">

            <p
                id="supplierStatusMessage"
                style="
                    margin:0;
                    line-height:1.6;
                    font-size:13px;
                    color:#46536a;
                "
            ></p>

        </div>


        <form
            method="POST"
            id="supplierStatusForm"
            action=""
        >

            @csrf
            @method('PATCH')


            <div class="supplier-modal-footer">

                <button
                    type="button"
                    class="btn light"
                    onclick="closeSupplierStatusModal()"
                >
                    Cancel
                </button>


                <button
                    type="submit"
                    id="supplierStatusConfirm"
                    class="btn danger"
                >
                    Confirm
                </button>

            </div>

        </form>

    </div>

</div>

@endsection


@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    const addModal =
        document.getElementById('addSupplierModal');

    const editModal =
        document.getElementById('editSupplierModal');

    const statusModal =
        document.getElementById('supplierStatusModal');


    /* =========================================================
       ADD MODAL
       ========================================================= */

    window.openAddSupplierModal = function () {

        addModal.classList.add('show');

        document.body.style.overflow = 'hidden';

    };


    window.closeAddSupplierModal = function () {

        addModal.classList.remove('show');

        document.body.style.overflow = '';

    };


    /* =========================================================
       EDIT MODAL
       ========================================================= */

    document
        .querySelectorAll('.edit-supplier-button')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    const id =
                        this.dataset.id;

                    const name =
                        this.dataset.name || '';

                    const contactPerson =
                        this.dataset.contactPerson || '';

                    const contactNumber =
                        this.dataset.contactNumber || '';

                    const email =
                        this.dataset.email || '';

                    const address =
                        this.dataset.address || '';


                    document.getElementById(
                        'editSupplierName'
                    ).value = name;


                    document.getElementById(
                        'editSupplierContactPerson'
                    ).value = contactPerson;


                    document.getElementById(
                        'editSupplierContactNumber'
                    ).value = contactNumber;


                    document.getElementById(
                        'editSupplierEmail'
                    ).value = email;


                    document.getElementById(
                        'editSupplierAddress'
                    ).value = address;


                    document.getElementById(
                        'editSupplierForm'
                    ).action =
                        '{{ url('/suppliers') }}/' + id;


                    editModal.classList.add('show');

                    document.body.style.overflow =
                        'hidden';

                }
            );

        });


    window.closeEditSupplierModal = function () {

        editModal.classList.remove('show');

        document.body.style.overflow = '';

    };


    /* =========================================================
       ARCHIVE / RESTORE MODAL
       ========================================================= */

    document
        .querySelectorAll('.supplier-toggle-button')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    const id =
                        this.dataset.id;

                    const name =
                        this.dataset.name || '';

                    const isActive =
                        this.dataset.active === '1';


                    const title =
                        document.getElementById(
                            'supplierStatusTitle'
                        );

                    const subtitle =
                        document.getElementById(
                            'supplierStatusSubtitle'
                        );

                    const message =
                        document.getElementById(
                            'supplierStatusMessage'
                        );

                    const confirmButton =
                        document.getElementById(
                            'supplierStatusConfirm'
                        );

                    const form =
                        document.getElementById(
                            'supplierStatusForm'
                        );


                    form.action =
                        '{{ url('/suppliers') }}/' +
                        id +
                        '/toggle';


                    if (isActive) {

                        title.textContent =
                            'Archive Supplier';

                        subtitle.textContent =
                            'The supplier will be marked as archived.';

                        message.textContent =
                            'Are you sure you want to archive "' +
                            name +
                            '"? The supplier record will remain stored in the system.';

                        confirmButton.textContent =
                            'Archive Supplier';

                        confirmButton.className =
                            'btn danger';

                    } else {

                        title.textContent =
                            'Restore Supplier';

                        subtitle.textContent =
                            'The supplier will become active again.';

                        message.textContent =
                            'Are you sure you want to restore "' +
                            name +
                            '"?';

                        confirmButton.textContent =
                            'Restore Supplier';

                        confirmButton.className =
                            'btn success';

                    }


                    statusModal.classList.add('show');

                    document.body.style.overflow =
                        'hidden';

                }
            );

        });


    window.closeSupplierStatusModal = function () {

        statusModal.classList.remove('show');

        document.body.style.overflow = '';

    };


    /* =========================================================
       CLICK OUTSIDE MODAL
       ========================================================= */

    document
        .querySelectorAll('.supplier-modal-overlay')
        .forEach(function (modal) {

            modal.addEventListener(
                'click',
                function (event) {

                    if (event.target === modal) {

                        modal.classList.remove('show');

                        document.body.style.overflow =
                            '';

                    }

                }
            );

        });


    /* =========================================================
       ESC KEY
       ========================================================= */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                document
                    .querySelectorAll(
                        '.supplier-modal-overlay.show'
                    )
                    .forEach(function (modal) {

                        modal.classList.remove('show');

                    });


                document.body.style.overflow =
                    '';

            }

        }
    );


    /* =========================================================
       OPEN ADD MODAL WHEN VALIDATION FAILS
       ========================================================= */

    @if($errors->any())

        openAddSupplierModal();

    @endif

});
</script>

@endpush