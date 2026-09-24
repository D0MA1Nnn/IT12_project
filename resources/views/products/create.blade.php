@extends('layouts.app')

@section('content')

<div class="page-header">
    <div>
        <h1>Add Product</h1>
        <p>Create a new product.</p>
    </div>

    <a href="{{ route('products.index') }}" class="btn btn-secondary">
        Back to Products
    </a>
</div>

<div class="card">

    <div class="card-header">
        <h2>Product Information</h2>
    </div>

    <form action="{{ route('products.store') }}" method="POST">
        @csrf

        <div class="form-group">
            <label for="category_id">
                Category
            </label>

            <select
                id="category_id"
                name="category_id"
                required
            >
                <option value="">-- Select Category --</option>

                @foreach ($categories as $category)
                    <option
                        value="{{ $category->category_id }}"
                        {{ old('category_id') == $category->category_id ? 'selected' : '' }}
                    >
                        {{ $category->category_name }}
                    </option>
                @endforeach

            </select>

            @error('category_id')
                <div class="form-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="product_name">
                Product Name
            </label>

            <input
                type="text"
                id="product_name"
                name="product_name"
                value="{{ old('product_name') }}"
                maxlength="150"
                required
                autofocus
            >

            @error('product_name')
                <div class="form-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-group">
            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="4"
            >{{ old('description') }}</textarea>

            @error('description')
                <div class="form-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-actions">

            <a
                href="{{ route('products.index') }}"
                class="btn btn-secondary"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="btn btn-primary"
            >
                Save Product
            </button>

        </div>

    </form>

</div>

@endsection