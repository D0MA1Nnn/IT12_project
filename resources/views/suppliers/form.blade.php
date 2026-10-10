@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')

<div class="top">

    <div>

        <h1>Edit Supplier</h1>

        <div class="muted">
            All supplier details are required. Products Offered is optional.
        </div>

    </div>

    <div class="who">
        Owner
    </div>

</div>


<div class="card">

    <form
        method="POST"
        action="{{ route('suppliers.update', $supplier) }}"
        id="editSupplierForm"
    >

        @csrf
        @method('PUT')


        <div class="form-grid">

            <div class="field">

                <label>
                    Supplier Name
                    <span style="color:#dc3545">*</span>
                </label>

                <input
                    type="text"
                    class="input"
                    name="supplier_name"
                    value="{{ old('supplier_name', $supplier->supplier_name) }}"
                    maxlength="60"
                    required
                >
                <small class="muted">Maximum 60 characters.</small>

            </div>


            <div class="field">

                <label>
                    Contact Person
                </label>

                <input
                    type="text"
                    class="input"
                    name="contact_person"
                    value="{{ old('contact_person', $supplier->contact_person) }}"
                    maxlength="60"
                    required
                >
                <small class="muted">Maximum 60 characters.</small>

            </div>


            <div class="field">

                <label>
                    Phone
                </label>

                <input
                    type="tel"
                    class="input"
                    name="contact_number"
                    value="{{ old('contact_number', $supplier->contact_number) }}"
                    maxlength="11"
                    minlength="11"
                    inputmode="numeric"
                    autocomplete="tel"
                    pattern="09[0-9]{9}"
                    data-digits-only
                    placeholder="09XXXXXXXXX"
                    title="Contact number must start with 09 and be exactly 11 digits."
                    required
                >

            </div>


            <div class="field">

                <label>
                    Email
                </label>

                <input
                    type="email"
                    class="input"
                    name="email"
                    value="{{ old('email', $supplier->email) }}"
                    maxlength="60"
                    pattern="[^\s@]+@([gG][mM][aA][iI][lL]|[yY][aA][hH][oO][oO]|[oO][uU][tT][lL][oO][oO][kK])\.[cC][oO][mM]"
                    title="Use an email ending with @gmail.com, @yahoo.com, or @outlook.com."
                    required
                >
                <small class="muted">Gmail, Yahoo or Outlook only. Maximum 60 characters.</small>

            </div>

        </div>


        <div class="field">

            <label>
                Address
            </label>

            <textarea
                class="input"
                name="address"
                rows="4"
                maxlength="200"
                required
            >{{ old('address', $supplier->address) }}</textarea>
            <small class="muted">Maximum 200 characters.</small>

        </div>


        <div
            style="
                display:flex;
                gap:8px;
                margin-top:22px;
            "
        >

            <a
                href="{{ route('suppliers.index') }}"
                class="btn light"
            >
                Cancel
            </a>


            <button
                type="button"
                class="btn primary"
                onclick="openEditSupplierModal()"
            >
                Save Changes
            </button>

        </div>

    </form>

</div>


{{-- CONFIRM UPDATE MODAL --}}

<div
    id="editSupplierModal"
    class="edit-supplier-modal"
>

    <div
        class="edit-supplier-backdrop"
        onclick="closeEditSupplierModal()"
    ></div>


    <div class="edit-supplier-box">

        <div class="edit-supplier-icon">
            ?
        </div>

        <h2>
            Save Changes?
        </h2>

        <p>
            Are you sure you want to update this supplier's information?
        </p>


        <div class="edit-supplier-actions">

            <button
                type="button"
                class="btn light"
                onclick="closeEditSupplierModal()"
            >
                Cancel
            </button>


            <button
                type="button"
                class="btn primary"
                onclick="submitEditSupplier()"
            >
                Yes, Save Changes
            </button>

        </div>

    </div>

</div>


<style>

    .edit-supplier-modal {
        position: fixed;
        inset: 0;
        z-index: 5000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .edit-supplier-modal.show {
        display: flex;
    }

    .edit-supplier-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(13, 25, 45, .55);
        backdrop-filter: blur(2px);
    }

    .edit-supplier-box {
        position: relative;
        z-index: 1;
        width: 420px;
        max-width: 100%;
        padding: 30px;
        background: #ffffff;
        border-radius: 14px;
        text-align: center;
        box-shadow: 0 25px 70px rgba(13, 25, 45, .20);
    }

    .edit-supplier-icon {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        border-radius: 50%;
        background: #edf4ff;
        color: #2468ee;
        font-size: 23px;
        font-weight: 800;
    }

    .edit-supplier-box h2 {
        margin: 0 0 8px;
        font-size: 20px;
    }

    .edit-supplier-box p {
        margin: 0;
        color: #7b879d;
        font-size: 12px;
        line-height: 1.6;
    }

    .edit-supplier-actions {
        display: flex;
        justify-content: center;
        gap: 8px;
        margin-top: 24px;
    }

</style>


<script>

    function openEditSupplierModal() {

        const form =
            document.getElementById('editSupplierForm');

        if (!form.checkValidity()) {

            form.reportValidity();

            return;
        }


        document
            .getElementById('editSupplierModal')
            .classList.add('show');

        document.body.style.overflow = 'hidden';
    }


    function closeEditSupplierModal() {

        document
            .getElementById('editSupplierModal')
            .classList.remove('show');

        document.body.style.overflow = '';
    }


    function submitEditSupplier() {

        document
            .getElementById('editSupplierForm')
            .submit();
    }


    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {
                closeEditSupplierModal();
            }

        }
    );

</script>

@endsection
