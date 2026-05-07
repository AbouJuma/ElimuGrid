@extends('layouts.master')

@section('title', __('Hostel Management'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">{{ __('Hostels') }}</h4>
                            @can('hostel-create')
                                <button class="btn btn-theme btn-sm" data-toggle="modal" data-target="#createHostelModal">
                                    <i class="fa fa-plus"></i> {{ __('Add Hostel') }}
                                </button>
                            @endcan
                        </div>
                        
                        <table aria-describedby="mydesc" class='table' id='table_hostels' data-toggle="table"
                               data-url="{{ route('hostel.hostels.list') }}" data-click-to-select="true" 
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                               data-show-columns="true" data-show-refresh="true"
                               data-mobile-responsive="true" data-sort-name="name" data-sort-order="asc"
                               data-escape="false">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="name" data-sortable="true">{{ __('Name') }}</th>
                                    <th scope="col" data-field="description">{{ __('Description') }}</th>
                                    <th scope="col" data-field="total_capacity">{{ __('Total Capacity') }}</th>
                                    <th scope="col" data-field="occupied_beds">{{ __('Occupied') }}</th>
                                    <th scope="col" data-field="available_beds">{{ __('Available') }}</th>
                                    <th scope="col" data-field="occupancy_rate">{{ __('Occupancy Rate') }}</th>
                                    <th scope="col" data-field="operate" data-events="hostelEvents">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Hostel Modal -->
    <div class="modal fade" id="createHostelModal" tabindex="-1" role="dialog" aria-labelledby="createHostelModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createHostelModalLabel">{{ __('Add New Hostel') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="createHostelForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="name">{{ __('Hostel Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="description">{{ __('Description') }}</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-theme">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Hostel Modal -->
    <div class="modal fade" id="editHostelModal" tabindex="-1" role="dialog" aria-labelledby="editHostelModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editHostelModalLabel">{{ __('Edit Hostel') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editHostelForm">
                    <input type="hidden" id="edit_hostel_id" name="hostel_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_name">{{ __('Hostel Name') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description">{{ __('Description') }}</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-theme">{{ __('Update') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        window.hostelEvents = {
            'click .edit-btn': function (e, value, row, index) {
                $('#edit_hostel_id').val(row.id);
                $('#edit_name').val(row.name);
                $('#edit_description').val(row.description);
                $('#editHostelModal').modal('show');
            },
            'click .delete-btn': function (e, value, row, index) {
                if (confirm('Are you sure you want to delete this hostel?')) {
                    $.ajax({
                        url: '{{ url("hostel/hostels") }}/' + row.id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                showSuccessToast(response.message);
                                $('#table_hostels').bootstrapTable('refresh');
                            } else {
                                showErrorToast(response.error);
                            }
                        },
                        error: function (xhr) {
                            showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                        }
                    });
                }
            }
        };

        $(document).ready(function () {
            // Create Hostel Form
            $('#createHostelForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route("hostel.hostels.store") }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.success) {
                            showSuccessToast(response.message);
                            $('#createHostelModal').modal('hide');
                            $('#createHostelForm')[0].reset();
                            $('#table_hostels').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.error);
                        }
                    },
                    error: function (xhr) {
                        showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                    }
                });
            });

            // Edit Hostel Form
            $('#editHostelForm').on('submit', function (e) {
                e.preventDefault();
                var hostelId = $('#edit_hostel_id').val();
                $.ajax({
                    url: '{{ url("hostel/hostels") }}/' + hostelId,
                    type: 'PUT',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.success) {
                            showSuccessToast(response.message);
                            $('#editHostelModal').modal('hide');
                            $('#table_hostels').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.error);
                        }
                    },
                    error: function (xhr) {
                        showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                    }
                });
            });
        });
    </script>
@endsection
