@extends('layouts.master')

@section('title', __('Room Management'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">
                                {{ __('Rooms') }}
                                @if(isset($hostel))
                                    - {{ $hostel->name }}
                                @endif
                            </h4>
                            <div>
                                <a href="{{ route('hostel.hostels.index') }}" class="btn btn-secondary btn-sm mr-2">
                                    <i class="fa fa-arrow-left"></i> {{ __('Back to Hostels') }}
                                </a>
                                @can('room-create')
                                    <button class="btn btn-theme btn-sm" data-toggle="modal" data-target="#createRoomModal">
                                        <i class="fa fa-plus"></i> {{ __('Add Room') }}
                                    </button>
                                @endcan
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filter_hostel">{{ __('Filter by Hostel') }}</label>
                                <select id="filter_hostel" class="form-control">
                                    <option value="">{{ __('All Hostels') }}</option>
                                    @foreach($hostels as $h)
                                        <option value="{{ $h->id }}" {{ isset($hostel) && $hostel->id == $h->id ? 'selected' : '' }}>
                                            {{ $h->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <table aria-describedby="mydesc" class='table' id='table_rooms' data-toggle="table"
                               data-url="{{ route('hostel.rooms.list') }}" data-click-to-select="true" 
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                               data-show-columns="true" data-show-refresh="true"
                               data-mobile-responsive="true" data-sort-name="room_number" data-sort-order="asc"
                               data-query-params="roomQueryParams"
                               data-escape="false">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="hostel_name">{{ __('Hostel') }}</th>
                                    <th scope="col" data-field="room_number" data-sortable="true">{{ __('Room Number') }}</th>
                                    <th scope="col" data-field="capacity">{{ __('Capacity') }}</th>
                                    <th scope="col" data-field="occupied_beds">{{ __('Occupied') }}</th>
                                    <th scope="col" data-field="available_beds">{{ __('Available') }}</th>
                                    <th scope="col" data-field="occupancy_rate">{{ __('Occupancy') }}</th>
                                    <th scope="col" data-field="status">{{ __('Status') }}</th>
                                    <th scope="col" data-field="operate" data-events="roomEvents">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Room Modal -->
    <div class="modal fade" id="createRoomModal" tabindex="-1" role="dialog" aria-labelledby="createRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createRoomModalLabel">{{ __('Add New Room') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="createRoomForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="hostel_id">{{ __('Hostel') }} <span class="text-danger">*</span></label>
                            <select class="form-control" id="hostel_id" name="hostel_id" required>
                                <option value="">{{ __('Select Hostel') }}</option>
                                @foreach($hostels as $h)
                                    <option value="{{ $h->id }}" {{ isset($hostel) && $hostel->id == $h->id ? 'selected' : '' }}>
                                        {{ $h->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="room_number">{{ __('Room Number') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="room_number" name="room_number" required>
                        </div>
                        <div class="form-group">
                            <label for="capacity">{{ __('Capacity') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="capacity" name="capacity" min="1" value="1" required>
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

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1" role="dialog" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoomModalLabel">{{ __('Edit Room') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editRoomForm">
                    <input type="hidden" id="edit_room_id" name="room_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="edit_room_number">{{ __('Room Number') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_room_number" name="room_number" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_capacity">{{ __('Capacity') }} <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_capacity" name="capacity" min="1" required>
                            <small class="form-text text-muted">{{ __('Cannot be less than currently occupied beds') }}</small>
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
        function roomQueryParams(params) {
            params.hostel_id = $('#filter_hostel').val();
            return params;
        }

        window.roomEvents = {
            'click .edit-btn': function (e, value, row, index) {
                $('#edit_room_id').val(row.id);
                $('#edit_room_number').val(row.room_number);
                $('#edit_capacity').val(row.capacity);
                $('#editRoomModal').modal('show');
            },
            'click .delete-btn': function (e, value, row, index) {
                if (confirm('Are you sure you want to delete this room?')) {
                    $.ajax({
                        url: '{{ url("hostel/rooms") }}/' + row.id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                showSuccessToast(response.message);
                                $('#table_rooms').bootstrapTable('refresh');
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
            // Filter change
            $('#filter_hostel').change(function () {
                $('#table_rooms').bootstrapTable('refresh');
            });

            // Create Room Form
            $('#createRoomForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route("hostel.rooms.store") }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.success) {
                            showSuccessToast(response.message);
                            $('#createRoomModal').modal('hide');
                            $('#createRoomForm')[0].reset();
                            $('#table_rooms').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.error);
                        }
                    },
                    error: function (xhr) {
                        showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                    }
                });
            });

            // Edit Room Form
            $('#editRoomForm').on('submit', function (e) {
                e.preventDefault();
                var roomId = $('#edit_room_id').val();
                $.ajax({
                    url: '{{ url("hostel/rooms") }}/' + roomId,
                    type: 'PUT',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.success) {
                            showSuccessToast(response.message);
                            $('#editRoomModal').modal('hide');
                            $('#table_rooms').bootstrapTable('refresh');
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
