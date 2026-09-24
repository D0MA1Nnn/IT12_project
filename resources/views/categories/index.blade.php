@extends('layouts.app')

@section('title', 'Categories')

@section('content')

<div class="top">
    <div>
        <h1>Categories</h1>
        <div class="muted">
            Organize construction materials by category
        </div>
    </div>

    <div class="who">Owner</div>
</div>

<div class="tabs">
    <a href="{{ route('products.index') }}">Products</a>
    <a class="active" href="{{ route('categories.index') }}">Categories</a>
    <a href="{{ route('units.index') }}">Units of Measure</a>
    <a href="{{ route('inventory.index') }}">Inventory</a>
</div>


{{-- =========================================================
     SEARCH / FILTER / ACTIONS
========================================================= --}}

<form
    method="GET"
    action="{{ route('categories.index') }}"
    class="category-filter-bar"
>

    {{-- Search --}}
    <div class="category-search-box">

        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
        >
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>

        <input
            type="text"
            name="search"
            class="category-filter-control category-search-input"
            value="{{ request('search') }}"
            placeholder="Search categories..."
        >

    </div>


    {{-- Status --}}
    <select
        name="status"
        class="category-filter-control category-filter-select"
    >
        <option value="">All Status</option>

        <option
            value="active"
            @selected(request('status') === 'active')
        >
            Active
        </option>

        <option
            value="inactive"
            @selected(request('status') === 'inactive')
        >
            Archived
        </option>
    </select>


    <button
        type="submit"
        class="category-filter-button"
    >
        Filter
    </button>


    @if(request()->filled('search') || request()->filled('status'))

        <a
            href="{{ route('categories.index') }}"
            class="category-clear-button"
        >
            Clear
        </a>

    @endif


    <div class="category-filter-spacer"></div>


    {{-- Back to Products --}}
    <a
        class="btn light"
        href="{{ route('products.index') }}"
    >
        ← Products
    </a>


    {{-- Add Category --}}
    <button
        type="button"
        class="btn primary"
        id="openAddCategoryModal"
    >
        + Add Category
    </button>

</form>


{{-- =========================================================
     CATEGORY TABLE
========================================================= --}}

<div class="category-table-container">

    <table class="table category-table">

        <thead>
            <tr>
                <th>Category</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($categories as $category)

                <tr>

                    <td>
                        <strong>
                            {{ $category->category_name }}
                        </strong>
                    </td>

                    <td>

                        @if($category->is_active)

                            <span class="category-status active">
                                Active
                            </span>

                        @else

                            <span class="category-status archived">
                                Archived
                            </span>

                        @endif

                    </td>

                    <td>

                        <div class="actions">

                            {{-- Edit --}}
                            <button
                                type="button"
                                class="btn light small category-edit-button"
                                data-id="{{ $category->category_id }}"
                                data-name="{{ $category->category_name }}"
                                data-update-url="{{ route('categories.update', $category) }}"
                            >
                                Edit
                            </button>


                            {{-- Archive --}}
                            @if($category->is_active)

                                <form
                                    method="POST"
                                    action="{{ route('categories.deactivate', $category) }}"
                                    class="category-status-form"
                                    data-category="{{ $category->category_name }}"
                                    data-action="archive"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="button"
                                        class="btn danger small category-status-button"
                                    >
                                        Archive
                                    </button>

                                </form>

                            {{-- Restore --}}
                            @else

                                <form
                                    method="POST"
                                    action="{{ route('categories.activate', $category) }}"
                                    class="category-status-form"
                                    data-category="{{ $category->category_name }}"
                                    data-action="restore"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="button"
                                        class="btn success small category-status-button"
                                    >
                                        Restore
                                    </button>

                                </form>

                            @endif

                        </div>

                    </td>

                </tr>

            @empty

                <tr>
                    <td
                        colspan="3"
                        class="category-empty"
                    >
                        No categories found.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

</div>


{{-- =========================================================
     PAGINATION
========================================================= --}}

@if(method_exists($categories, 'hasPages') && $categories->hasPages())

    <div class="category-pagination">

        <div class="pagination-info">

            Showing
            {{ $categories->firstItem() }}
            to
            {{ $categories->lastItem() }}
            of
            {{ $categories->total() }}
            categories

        </div>

        <div class="compact-pagination">
            @if ($categories->onFirstPage())
                <span class="page-link disabled">&lsaquo; Previous</span>
            @else
                <a class="page-link" href="{{ $categories->previousPageUrl() }}">&lsaquo; Previous</a>
            @endif

            @for ($page = 1; $page <= $categories->lastPage(); $page++)
                @if ($page === $categories->currentPage())
                    <span class="page-link active">{{ $page }}</span>
                @else
                    <a class="page-link" href="{{ $categories->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if ($categories->hasMorePages())
                <a class="page-link" href="{{ $categories->nextPageUrl() }}">Next &rsaquo;</a>
            @else
                <span class="page-link disabled">Next &rsaquo;</span>
            @endif
        </div>

    </div>

@endif


{{-- =========================================================
     ADD CATEGORY MODAL
========================================================= --}}

<div
    class="category-modal-overlay"
    id="addCategoryModal"
>

    <div
        class="category-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="addCategoryTitle"
    >

        <div class="category-modal-header">

            <div>

                <h2 id="addCategoryTitle">
                    Add Category
                </h2>

                <p>
                    Create a category for construction materials.
                </p>

            </div>

            <button
                type="button"
                class="category-modal-close"
                id="closeAddCategoryModal"
                aria-label="Close"
            >
                ×
            </button>

        </div>


        <form
            method="POST"
            action="{{ route('categories.store') }}"
            id="addCategoryForm"
        >

            @csrf


            <div class="field">

                <label for="category_name">
                    Category Name
                </label>

                <input
                    id="category_name"
                    type="text"
                    name="category_name"
                    class="input"
                    value="{{ old('category_name') }}"
                    placeholder="Enter category name"
                    maxlength="100"
                    required
                >

            </div>


            <div class="category-modal-actions">

                <button
                    type="button"
                    class="btn light"
                    id="cancelAddCategory"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn primary"
                >
                    Add Category
                </button>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     EDIT CATEGORY MODAL
========================================================= --}}

<div
    class="category-modal-overlay"
    id="editCategoryModal"
>
    <div
        class="category-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editCategoryTitle"
    >
        <div class="category-modal-header">
            <div>
                <h2 id="editCategoryTitle">
                    Edit Category
                </h2>

                <p>
                    Update the selected construction material category.
                </p>
            </div>

            <button
                type="button"
                class="category-modal-close"
                id="closeEditCategoryModal"
                aria-label="Close"
            >
                ×
            </button>
        </div>

        <form
            method="POST"
            action=""
            id="editCategoryForm"
        >
            @csrf
            @method('PUT')

            <div class="field">
                <label for="edit_category_name">
                    Category Name
                </label>

                <input
                    id="edit_category_name"
                    type="text"
                    name="category_name"
                    class="input"
                    placeholder="Enter category name"
                    maxlength="100"
                    required
                >
            </div>

            <div class="category-modal-actions">
                <button
                    type="button"
                    class="btn light"
                    id="cancelEditCategory"
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
     ARCHIVE / RESTORE CONFIRMATION MODAL
========================================================= --}}

<div
    class="category-modal-overlay"
    id="categoryStatusModal"
>

    <div
        class="category-confirm-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="categoryConfirmTitle"
    >

        <div
            class="category-confirm-icon"
            id="categoryConfirmIcon"
        >
            !
        </div>


        <h2 id="categoryConfirmTitle">
            Confirm Action
        </h2>


        <p id="categoryConfirmMessage">
            Are you sure you want to continue?
        </p>


        <div class="category-modal-actions">

            <button
                type="button"
                class="btn light"
                id="cancelCategoryStatus"
            >
                Cancel
            </button>

            <button
                type="button"
                class="btn danger"
                id="confirmCategoryStatus"
            >
                Confirm
            </button>

        </div>

    </div>

</div>


<style>

/* =========================================================
   FILTER BAR
========================================================= */

.category-filter-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}


/* =========================================================
   SEARCH
========================================================= */

.category-search-box {
    position: relative;
    width: 235px;
}

.category-search-box svg {
    position: absolute;
    left: 12px;
    top: 50%;

    width: 16px;
    height: 16px;

    transform: translateY(-50%);

    color: #94a3b8;

    pointer-events: none;
}


.category-filter-control {
    height: 40px;

    border: 1px solid #dfe5ee;
    border-radius: 7px;

    background: #ffffff;
    color: #182033;

    font-family: inherit;
    font-size: 12px;
    font-weight: 400;

    outline: none;
}


.category-search-input {
    width: 100%;

    padding:
        0
        12px
        0
        38px;
}


.category-search-input::placeholder {
    color: #7b879d;
    font-size: 12px;
    font-weight: 400;
}


.category-filter-select {
    width: 165px;

    padding:
        0
        34px
        0
        12px;

    font-size: 12px;

    cursor: pointer;
}


.category-filter-control:focus {
    border-color: #2468ee;

    box-shadow:
        0 0 0 3px
        rgba(36, 104, 238, .08);
}


.category-filter-button {
    height: 40px;

    padding: 0 20px;

    border: 0;
    border-radius: 7px;

    background: #2468ee;
    color: #ffffff;

    font-family: inherit;
    font-size: 12px;
    font-weight: 700;

    cursor: pointer;
}


.category-filter-button:hover {
    background: #1d5edc;
}


.category-clear-button {
    height: 40px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    padding: 0 18px;

    border-radius: 7px;

    background: #eef2f8;

    color: #46536a;

    text-decoration: none;

    font-size: 12px;
    font-weight: 700;
}


.category-filter-spacer {
    flex: 1;
}


/* =========================================================
   TABLE
========================================================= */

.category-table-container {
    width: 100%;

    overflow-x: auto;

    background: #ffffff;
}


.category-table th:nth-child(1),
.category-table td:nth-child(1) {
    width: 55%;
}


.category-table th:nth-child(2),
.category-table td:nth-child(2) {
    width: 20%;
}


.category-table th:nth-child(3),
.category-table td:nth-child(3) {
    width: 25%;
}


.category-table .actions {
    display: flex;
    align-items: center;
    gap: 6px;
}


.category-table .actions form {
    margin: 0;
}


.category-empty {
    text-align: center;

    padding: 35px !important;

    color: #7b879d;
}


/* =========================================================
   STATUS
========================================================= */

.category-status {
    font-size: 11px;
    font-weight: 600;
}


.category-status.active {
    color: #15803d;
}


.category-status.archived {
    color: #64748b;
}


/* =========================================================
   PAGINATION
========================================================= */

.category-pagination {
    display: flex;

    align-items: center;
    justify-content: space-between;

    margin-top: 20px;

    gap: 20px;

    flex-wrap: wrap;
}


.pagination-info {
    font-size: 12px;

    color: #7b879d;
}


/* =========================================================
   MODAL BACKGROUND
========================================================= */

.category-modal-overlay {
    position: fixed;

    inset: 0;

    z-index: 100300;

    display: none;

    align-items: center;
    justify-content: center;

    padding: 20px;

    background:
        rgba(
            13,
            25,
            45,
            .62
        );

    backdrop-filter: blur(4px);
}


.category-modal-overlay.show {
    display: flex;
}


/* =========================================================
   ADD CATEGORY MODAL
========================================================= */

.category-modal {
    width: 100%;
    max-width: 460px;

    background: #ffffff;

    border-radius: 16px;

    padding: 28px;

    box-shadow:
        0
        28px
        80px
        rgba(15, 23, 42, .28);
}


.category-modal-header {
    display: flex;

    justify-content: space-between;
    align-items: flex-start;

    gap: 20px;

    margin-bottom: 25px;
}


.category-modal-header h2 {
    margin: 0 0 6px;

    font-size: 21px;
}


.category-modal-header p {
    margin: 0;

    color: #7b879d;

    font-size: 12px;
}


.category-modal-close {
    width: 34px;
    height: 34px;

    border: 0;
    border-radius: 7px;

    background: #eef2f8;

    color: #46536a;

    font-size: 22px;

    cursor: pointer;
}


.category-modal-actions {
    display: flex;

    justify-content: flex-end;

    gap: 10px;

    margin-top: 24px;
}


/* =========================================================
   CONFIRMATION MODAL
========================================================= */

.category-confirm-modal {
    width: 100%;
    max-width: 430px;

    background: #ffffff;

    border-radius: 16px;

    padding: 32px;

    text-align: center;

    box-shadow:
        0
        28px
        80px
        rgba(15, 23, 42, .28);
}


.category-confirm-icon {
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

    background: #fff1f2;

    color: #dc3545;

    font-size: 22px;
    font-weight: 800;
}


.category-confirm-modal h2 {
    margin: 0 0 10px;

    font-size: 20px;
}


.category-confirm-modal p {
    margin: 0 0 25px;

    color: #7b879d;

    font-size: 13px;

    line-height: 1.6;
}


.category-confirm-modal .category-modal-actions {
    justify-content: center;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media(max-width: 900px) {

    .category-search-box {
        width: 100%;
    }


    .category-filter-select {
        flex: 1;

        min-width: 150px;
    }


    .category-filter-spacer {
        display: none;
    }

}


@media(max-width: 600px) {

    .category-filter-select,
    .category-filter-button,
    .category-clear-button {
        width: 100%;
    }


    .category-modal-actions {
        flex-direction: column-reverse;
    }


    .category-modal-actions .btn {
        width: 100%;
    }

}


.tabs { display:flex; gap:10px; margin:22px 0 24px; flex-wrap:wrap; }
.tabs a { padding:11px 20px; border-radius:8px; background:#fff; color:#334155; text-decoration:none; font-size:14px; }
.tabs a.active { background:#2563eb; color:#fff; }
.compact-pagination { display:flex; align-items:center; gap:6px; flex-wrap:wrap; justify-content:flex-end; }
.compact-pagination .page-link { display:inline-flex; align-items:center; justify-content:center; min-width:34px; height:34px; padding:0 10px; border:1px solid #dbe3ef; border-radius:8px; background:#fff; color:#334155; font-size:13px; font-weight:700; text-decoration:none; line-height:1; }
.compact-pagination .page-link:hover { background:#f1f5f9; }
.compact-pagination .page-link.active { background:#2563eb; border-color:#2563eb; color:#fff; }
.compact-pagination .page-link.disabled { color:#94a3b8; background:#f8fafc; cursor:default; }
</style>


@push('scripts')

<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | ADD CATEGORY MODAL
    |--------------------------------------------------------------------------
    */

    const addModal =
        document.getElementById('addCategoryModal');

    const openAddButton =
        document.getElementById('openAddCategoryModal');

    const closeAddButton =
        document.getElementById('closeAddCategoryModal');

    const cancelAddButton =
        document.getElementById('cancelAddCategory');

    const categoryInput =
        document.getElementById('category_name');


    function openAddModal() {

        addModal.classList.add('show');

        document.body.style.overflow = 'hidden';

        setTimeout(function () {

            categoryInput.focus();

        }, 100);

    }


    function closeAddModal() {

        addModal.classList.remove('show');

        document.body.style.overflow = '';

    }


    openAddButton.addEventListener(
        'click',
        openAddModal
    );


    closeAddButton.addEventListener(
        'click',
        closeAddModal
    );


    cancelAddButton.addEventListener(
        'click',
        closeAddModal
    );


    addModal.addEventListener(
        'click',
        function (event) {

            if (event.target === addModal) {

                closeAddModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Automatically reopen Add Category modal when validation fails
    |--------------------------------------------------------------------------
    */

    @if($errors->has('category_name'))

        openAddModal();

    @endif



    /*
    |--------------------------------------------------------------------------
    | EDIT CATEGORY MODAL
    |--------------------------------------------------------------------------
    */

    const editModal =
        document.getElementById('editCategoryModal');

    const editForm =
        document.getElementById('editCategoryForm');

    const editInput =
        document.getElementById('edit_category_name');

    const closeEditButton =
        document.getElementById('closeEditCategoryModal');

    const cancelEditButton =
        document.getElementById('cancelEditCategory');


    function openEditModal(button) {

        editForm.action =
            button.dataset.updateUrl;

        editInput.value =
            button.dataset.name || '';

        editModal.classList.add('show');

        document.body.style.overflow = 'hidden';

        setTimeout(function () {
            editInput.focus();
            editInput.select();
        }, 100);
    }


    function closeEditModal() {

        editModal.classList.remove('show');

        document.body.style.overflow = '';

        editForm.action = '';

        editInput.value = '';
    }


    document
        .querySelectorAll('.category-edit-button')
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {
                    openEditModal(this);
                }
            );

        });


    closeEditButton.addEventListener(
        'click',
        closeEditModal
    );


    cancelEditButton.addEventListener(
        'click',
        closeEditModal
    );


    editModal.addEventListener(
        'click',
        function (event) {

            if (event.target === editModal) {
                closeEditModal();
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ARCHIVE / RESTORE MODAL
    |--------------------------------------------------------------------------
    */

    let pendingCategoryForm = null;


    const statusModal =
        document.getElementById(
            'categoryStatusModal'
        );


    const statusTitle =
        document.getElementById(
            'categoryConfirmTitle'
        );


    const statusMessage =
        document.getElementById(
            'categoryConfirmMessage'
        );


    const statusIcon =
        document.getElementById(
            'categoryConfirmIcon'
        );


    const confirmStatusButton =
        document.getElementById(
            'confirmCategoryStatus'
        );


    const cancelStatusButton =
        document.getElementById(
            'cancelCategoryStatus'
        );


    document
        .querySelectorAll(
            '.category-status-button'
        )
        .forEach(function (button) {

            button.addEventListener(
                'click',
                function () {

                    pendingCategoryForm =
                        this.closest(
                            '.category-status-form'
                        );


                    if (!pendingCategoryForm) {
                        return;
                    }


                    const categoryName =
                        pendingCategoryForm.dataset.category;


                    const action =
                        pendingCategoryForm.dataset.action;


                    if (action === 'archive') {

                        statusTitle.textContent =
                            'Archive Category';


                        statusMessage.textContent =
                            'Are you sure you want to archive "' +
                            categoryName +
                            '"? Existing records will remain preserved.';


                        statusIcon.textContent = '!';

                        statusIcon.style.background =
                            '#fff1f2';

                        statusIcon.style.color =
                            '#dc3545';


                        confirmStatusButton.textContent =
                            'Archive';

                        confirmStatusButton.className =
                            'btn danger';

                    } else {

                        statusTitle.textContent =
                            'Restore Category';


                        statusMessage.textContent =
                            'Are you sure you want to restore "' +
                            categoryName +
                            '"?';


                        statusIcon.textContent = '✓';

                        statusIcon.style.background =
                            '#ecfdf3';

                        statusIcon.style.color =
                            '#12a957';


                        confirmStatusButton.textContent =
                            'Restore';

                        confirmStatusButton.className =
                            'btn success';

                    }


                    statusModal.classList.add('show');

                    document.body.style.overflow =
                        'hidden';

                }
            );

        });


    function closeStatusModal() {

        statusModal.classList.remove('show');

        document.body.style.overflow = '';

        pendingCategoryForm = null;

        confirmStatusButton.disabled = false;

    }


    cancelStatusButton.addEventListener(
        'click',
        closeStatusModal
    );


    confirmStatusButton.addEventListener(
        'click',
        function () {

            if (!pendingCategoryForm) {
                return;
            }


            confirmStatusButton.disabled = true;

            confirmStatusButton.textContent =
                'Please wait...';


            pendingCategoryForm.submit();

        }
    );


    statusModal.addEventListener(
        'click',
        function (event) {

            if (event.target === statusModal) {

                closeStatusModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }


            if (statusModal.classList.contains('show')) {

                closeStatusModal();

                return;

            }


            if (editModal.classList.contains('show')) {

                closeEditModal();

                return;

            }


            if (addModal.classList.contains('show')) {

                closeAddModal();

            }

        }
    );

});

</script>

@endpush

@endsection