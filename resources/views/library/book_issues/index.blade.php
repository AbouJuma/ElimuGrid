@extends('layouts.master')

@section('title', __('Book Issues Management'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Issue Book to Student') }}</h4>
                        <form action="{{ route('library.issues.store') }}" method="POST" class="create-form" id="issue-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="class_id">{{ __('Select Class') }} <span class="text-danger">*</span></label>
                                    <select name="class_id" id="class_id" class="form-control select2" required>
                                        <option value="">{{ __('Select Class') }}</option>
                                        @foreach ($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="student_id">{{ __('Select Student') }} <span class="text-danger">*</span></label>
                                    <select name="student_id" id="student_id" class="form-control select2" required disabled>
                                        <option value="">{{ __('First select class') }}</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="book_id">{{ __('Select Book') }} <span class="text-danger">*</span></label>
                                    <select name="book_id" id="book_id" class="form-control select2" required>
                                        <option value="">{{ __('Select Book') }}</option>
                                        @foreach ($books as $book)
                                            <option value="{{ $book->id }}">{{ $book->title }} - {{ $book->author }} (Avail: {{ $book->available_quantity }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="issue_date">{{ __('Issue Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="issue_date" id="issue_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="return_date">{{ __('Expected Return Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" name="return_date" id="return_date" class="form-control" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row mt-4">
                                <div class="col-md-12 col-sm-12 col-12">
                                    <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value="{{ __('Issue Book') }}">
                                    <input class="btn btn-secondary float-right" type="reset" value="{{ __('Reset') }}">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Borrowed Books') }}</h4>
                        <div class="d-block">
                            <div class="col-12 text-right d-flex justify-content-end mb-3">
                                <a href="{{ route('library.reports') }}" class="btn btn-info mr-2">{{ __('View Reports') }}</a>
                            </div>
                        </div>
                        <div id="toolbar">
                            <label for="filter_class" class="filter-menu">{{ __('Filter by Class') }}</label>
                            <select name="filter_class" id="filter_class" class="form-control">
                                <option value="">{{ __('All Classes') }}</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}">{{ $class->full_name }}</option>
                                @endforeach
                            </select>
                            <label for="filter_student" class="filter-menu ml-2">{{ __('Filter by Student') }}</label>
                            <select name="filter_student" id="filter_student" class="form-control">
                                <option value="">{{ __('All Students') }}</option>
                            </select>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                               data-url="{{ route('library.issues.borrowed') }}" data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                               data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                               data-mobile-responsive="true" data-sort-name="id" data-toolbar="#toolbar" data-sort-order="desc"
                               data-query-params="issueQueryParams"
                               data-response-handler="responseHandler"
                               >
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="false" data-visible="false">{{ __('ID') }}</th>
                                <th scope="col" data-field="no">{{ __('No.') }}</th>
                                <th scope="col" data-field="book_title">{{ __('Book Title') }}</th>
                                <th scope="col" data-field="book_author">{{ __('Author') }}</th>
                                <th scope="col" data-field="book_isbn">{{ __('ISBN') }}</th>
                                <th scope="col" data-field="student_name">{{ __('Student') }}</th>
                                <th scope="col" data-field="class_name">{{ __('Class') }}</th>
                                <th scope="col" data-field="issue_date">{{ __('Issue Date') }}</th>
                                <th scope="col" data-field="return_date">{{ __('Due Date') }}</th>
                                <th scope="col" data-field="late_days">{{ __('Late Days') }}</th>
                                <th scope="col" data-field="fine_amount">{{ __('Fine') }}</th>
                                <th scope="col" data-field="status_badge">{{ __('Status') }}</th>
                                <th scope="col" data-field="operate" data-escape="false">{{ __('Action') }}</th>
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
        // Dynamic student loading based on class selection
        $('#class_id').change(function() {
            var classId = $(this).val();
            var studentSelect = $('#student_id');

            if (classId) {
                $.ajax({
                    url: '{{ route("library.issues.get-students") }}',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(response) {
                        studentSelect.empty();
                        studentSelect.append('<option value="">{{ __("Select Student") }}</option>');
                        $.each(response, function(index, student) {
                            studentSelect.append('<option value="' + student.id + '">' + student.text + '</option>');
                        });
                        studentSelect.prop('disabled', false);
                    },
                    error: function() {
                        showErrorToast('{{ __("Failed to load students") }}');
                    }
                });
            } else {
                studentSelect.empty();
                studentSelect.append('<option value="">{{ __("First select class") }}</option>');
                studentSelect.prop('disabled', true);
            }
        });

        // Filter handling
        $('#filter_class').change(function() {
            var classId = $(this).val();
            var studentSelect = $('#filter_student');

            if (classId) {
                $.ajax({
                    url: '{{ route("library.issues.get-students") }}',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(response) {
                        studentSelect.empty();
                        studentSelect.append('<option value="">{{ __("All Students") }}</option>');
                        $.each(response, function(index, student) {
                            studentSelect.append('<option value="' + student.id + '">' + student.text + '</option>');
                        });
                    }
                });
            } else {
                studentSelect.empty();
                studentSelect.append('<option value="">{{ __("All Students") }}</option>');
            }
            $('#table_list').bootstrapTable('refresh');
        });

        $('#filter_student').change(function() {
            $('#table_list').bootstrapTable('refresh');
        });

        function issueQueryParams(params) {
            params.class_id = $('#filter_class').val();
            params.student_id = $('#filter_student').val();
            return params;
        }
        
        function responseHandler(res) {
            console.log('Response received:', res);
            return res;
        }
        
        // Add error handling for the table
        $('#table_list').on('error.bs.table', function (e, status) {
            console.error('Table error:', status);
            console.error('Event:', e);
        });
        
        // Add loading event
        $('#table_list').on('load-error.bs.table', function (status, jqXHR) {
            console.error('Load error:', status, jqXHR);
        });
        
        // Add success event
        $('#table_list').on('load-success.bs.table', function (data) {
            console.log('Load success:', data);
        });

        // Return book action - Direct click handler
        $(document).on('click', '.return-btn', function(e) {
            e.preventDefault();
            var bookId = $(this).data('id');
            if (confirm('{{ __("Are you sure you want to return this book?") }}')) {
                $.ajax({
                    url: '{{ url("library/issues") }}/' + bookId + '/return',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        _method: 'POST'
                    },
                    success: function(response) {
                        $('#table_list').bootstrapTable('refresh');
                        showSuccessToast(response.message);
                    },
                    error: function(xhr) {
                        console.error('Return book error:', xhr);
                        var errorMsg = 'Error returning book';
                        if (xhr.responseJSON) {
                            errorMsg = xhr.responseJSON.error || xhr.responseJSON.message || errorMsg;
                        } else if (xhr.status === 500) {
                            errorMsg = 'Server error (500). Check server logs.';
                        } else if (xhr.status === 404) {
                            errorMsg = 'Return endpoint not found (404)';
                        }
                        showErrorToast(errorMsg);
                    }
                });
            }
        });
    </script>
@endsection
