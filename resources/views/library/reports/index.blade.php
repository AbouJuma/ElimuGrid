@extends('layouts.master')

@section('title', __('Library Reports'))

@section('content')
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Total Books') }}</h5>
                        <h2>{{ $stats['total_books'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Available Books') }}</h5>
                        <h2>{{ $stats['available_books'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Borrowed Books') }}</h5>
                        <h2>{{ $stats['borrowed_books'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Overdue Books') }}</h5>
                        <h2>{{ $stats['overdue_books'] }}</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Returned Books Report -->
        <div class="row mt-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Returned Books') }}</h4>
                        <div id="toolbar-returned">
                            <div class="row">
                                <div class="col-md-2">
                                    <label for="from_date">{{ __('From Date') }}</label>
                                    <input type="date" id="from_date" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="to_date">{{ __('To Date') }}</label>
                                    <input type="date" id="to_date" class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label for="filter_class_returned">{{ __('Class') }}</label>
                                    <select id="filter_class_returned" class="form-control">
                                        <option value="">{{ __('All Classes') }}</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-theme btn-block" onclick="refreshReturnedTable()">{{ __('Filter') }}</button>
                                </div>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_returned' data-toggle="table"
                               data-url="{{ route('library.issues.returned') }}" data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                               data-show-columns="true" data-show-refresh="true"
                               data-mobile-responsive="true" data-sort-name="actual_return_date" data-sort-order="desc"
                               data-query-params="returnedQueryParams"
                               data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="book_title">{{ __('Book Title') }}</th>
                                <th scope="col" data-field="book_author">{{ __('Author') }}</th>
                                <th scope="col" data-field="student_name">{{ __('Student') }}</th>
                                <th scope="col" data-field="class_name">{{ __('Class') }}</th>
                                <th scope="col" data-field="issue_date">{{ __('Issue Date') }}</th>
                                <th scope="col" data-field="actual_return_date">{{ __('Returned Date') }}</th>
                                <th scope="col" data-field="late_days">{{ __('Late Days') }}</th>
                                <th scope="col" data-field="fine_amount">{{ __('Fine Amount') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Books Report -->
        <div class="row mt-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Overdue Books') }}</h4>
                        <div id="toolbar-overdue">
                            <div class="row">
                                <div class="col-md-3">
                                    <label for="filter_class_overdue">{{ __('Class') }}</label>
                                    <select id="filter_class_overdue" class="form-control">
                                        <option value="">{{ __('All Classes') }}</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>&nbsp;</label>
                                    <button class="btn btn-theme btn-block" onclick="refreshOverdueTable()">{{ __('Filter') }}</button>
                                </div>
                            </div>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_overdue' data-toggle="table"
                               data-url="{{ route('library.issues.overdue') }}" data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                               data-show-columns="true" data-show-refresh="true"
                               data-mobile-responsive="true" data-sort-name="late_days" data-sort-order="desc"
                               data-query-params="overdueQueryParams"
                               data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="book_title">{{ __('Book Title') }}</th>
                                <th scope="col" data-field="book_author">{{ __('Author') }}</th>
                                <th scope="col" data-field="student_name">{{ __('Student') }}</th>
                                <th scope="col" data-field="student_email">{{ __('Student Email') }}</th>
                                <th scope="col" data-field="class_name">{{ __('Class') }}</th>
                                <th scope="col" data-field="issue_date">{{ __('Issue Date') }}</th>
                                <th scope="col" data-field="return_date">{{ __('Due Date') }}</th>
                                <th scope="col" data-field="late_days">{{ __('Late Days') }}</th>
                                <th scope="col" data-field="fine_amount">{{ __('Fine Amount') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function returnedQueryParams(params) {
            params.from_date = $('#from_date').val();
            params.to_date = $('#to_date').val();
            params.class_id = $('#filter_class_returned').val();
            return params;
        }

        function overdueQueryParams(params) {
            params.class_id = $('#filter_class_overdue').val();
            return params;
        }

        function refreshReturnedTable() {
            $('#table_returned').bootstrapTable('refresh');
        }

        function refreshOverdueTable() {
            $('#table_overdue').bootstrapTable('refresh');
        }

        // Load students when class is selected for filters
        $('#filter_class_returned').change(function() {
            refreshReturnedTable();
        });

        $('#filter_class_overdue').change(function() {
            refreshOverdueTable();
        });
    </script>
@endsection
