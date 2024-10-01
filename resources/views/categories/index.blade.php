@extends('layouts.app')
@push('third_party_stylesheets')
    @include('layouts.datatables_css')
@endpush

@section('content')
    <div class="page-header">
        <div class="page-leftheader">
            <h4 class="page-title mb-0">@lang('models/categories.singular')</h4>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="#">
                        <i class="fe fe-layout mr-2 fs-14"></i>
                        @lang('models/categories.list')
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <a href="#">list</a>
                </li>
            </ol>
        </div>
        <div class="page-rightheader">
            @if (auth()->user()->checkAllowdByModelID(2))
                <div class="btn btn-list">
                    <a class="btn btn-primary" href="{{ route('categories.create') }}">
                        <i class="fe fe-plus mr-1"></i>
                        @lang('crud.add_new')
                    </a>
                </div>
            @endif

        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-head">
                    <div class="card-header">
                        <h4 class="inline">@lang('models/categories.singular')</h4>
                        <div class="heading-elements mt-0">

                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        @include('categories.custom_table')
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @include('layouts.datatables_js')
    @yield('table_common_script')
@endsection
