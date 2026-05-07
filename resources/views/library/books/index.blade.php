@extends('layouts.master')

@section('title', __('Books Management'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Add Book') }}</h4>
                        <form action="{{ route('library.books.store') }}" method="POST" class="create-form" id="book-form">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="title">{{ __('Title') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="title" id="title" class="form-control" placeholder="{{ __('Enter book title') }}" required>
                                </div>
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="author">{{ __('Author') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="author" id="author" class="form-control" placeholder="{{ __('Enter author name') }}" required>
                                </div>
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="isbn">{{ __('ISBN') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="isbn" id="isbn" class="form-control" placeholder="{{ __('Enter ISBN number') }}" required>
                                </div>
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="category">{{ __('Category') }}</label>
                                    <input type="text" name="category" id="category" class="form-control" placeholder="{{ __('Enter category') }}">
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-md-3 col-sm-12">
                                    <label for="quantity">{{ __('Quantity') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="1" required>
                                </div>
                            </div>
                            <hr>
                            <div class="row mt-4">
                                <div class="col-md-12 col-sm-12 col-12">
                                    <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value="{{ __('Submit') }}">
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
                        <h4 class="card-title">{{ __('Books List') }}</h4>
                        <div class="d-block">
                            <div class="">
                                <div class="col-12 text-right d-flex justify-content-end text-right align-items-end">
                                    <b><a href="#" class="table-list-type active mr-2" data-id="0">{{ __('All') }}</a></b>
                                </div>
                            </div>
                        </div>
                        <div id="toolbar">
                            <label for="filter_category" class="filter-menu">{{ __('Category') }}</label>
                            <select name="filter_category" id="filter_category" class="form-control">
                                <option value="">{{ __('All Categories') }}</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <table aria-describedby="mydesc" class='table' id='table_list' data-toggle="table"
                               data-url="{{ route('library.books.list') }}" data-click-to-select="true" data-side-pagination="server"
                               data-pagination="true" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                               data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                               data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                               data-mobile-responsive="true" data-sort-name="id" data-toolbar="#toolbar" data-sort-order="desc"
                               data-maintain-selected="true" data-export-data-type='all'
                               data-export-options='{ "fileName": "books-list-<?= date('d-m-y') ?>" ,"ignoreColumn":["operate"]}'
                               data-show-export="true" data-query-params="bookQueryParams"
                               data-escape="true">
                            <thead>
                            <tr>
                                <th scope="col" data-field="id" data-sortable="false" data-visible="false">{{ __('ID') }}</th>
                                <th scope="col" data-field="no">{{ __('No.') }}</th>
                                <th scope="col" data-field="title" data-sortable="false">{{ __('Title') }}</th>
                                <th scope="col" data-field="author">{{ __('Author') }}</th>
                                <th scope="col" data-field="isbn">{{ __('ISBN') }}</th>
                                <th scope="col" data-field="category">{{ __('Category') }}</th>
                                <th scope="col" data-field="quantity">{{ __('Total Qty') }}</th>
                                <th scope="col" data-field="available_quantity">{{ __('Available') }}</th>
                                <th scope="col" data-field="issued_quantity">{{ __('Issued') }}</th>
                                <th scope="col" data-field="status" data-formatter="statusFormatter">{{ __('Status') }}</th>
                                <th scope="col" data-field="operate" data-escape="false">{{ __('Action') }}</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Book Modal -->
    <div class="modal fade" id="editBookModal" tabindex="-1" role="dialog" aria-labelledby="editBookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBookModalLabel">{{ __('Edit Book') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="" method="POST" id="edit-book-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="edit_title">{{ __('Title') }} <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="edit_title" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_author">{{ __('Author') }} <span class="text-danger">*</span></label>
                                <input type="text" name="author" id="edit_author" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="edit_isbn">{{ __('ISBN') }} <span class="text-danger">*</span></label>
                                <input type="text" name="isbn" id="edit_isbn" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label for="edit_category">{{ __('Category') }}</label>
                                <input type="text" name="category" id="edit_category" class="form-control">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label for="edit_quantity">{{ __('Quantity') }} <span class="text-danger">*</span></label>
                                <input type="number" name="quantity" id="edit_quantity" class="form-control" min="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                        <button type="submit" class="btn btn-theme">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function statusFormatter(value, row) {
            if (row.available_quantity > 0) {
                return '<span class="badge badge-success">' + value + '</span>';
            }
            return '<span class="badge badge-danger">' + value + '</span>';
        }

        function bookQueryParams(params) {
            params.category = $('#filter_category').val();
            return params;
        }

        $('#filter_category').change(function() {
            $('#table_list').bootstrapTable('refresh');
        });

        // Edit Book
        window.operateEvents = {
            'click .edit-btn': function(e, value, row, index) {
                $('#edit_id').val(row.id);
                $('#edit_title').val(row.title);
                $('#edit_author').val(row.author);
                $('#edit_isbn').val(row.isbn);
                $('#edit_category').val(row.category);
                $('#edit_quantity').val(row.quantity);
                $('#edit-book-form').attr('action', '{{ route("library.books.update", "") }}/' + row.id);
                $('#editBookModal').modal('show');
            }
        };

        // Handle edit form submission
        $('#edit-book-form').on('submit', function(e) {
            e.preventDefault();
            var url = $(this).attr('action');
            var data = $(this).serialize();

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
                success: function(response) {
                    $('#editBookModal').modal('hide');
                    $('#table_list').bootstrapTable('refresh');
                    showSuccessToast(response.message);
                },
                error: function(xhr) {
                    showErrorToast(xhr.responseJSON?.message || 'Error updating book');
                }
            });
        });
    </script>
@endsection
