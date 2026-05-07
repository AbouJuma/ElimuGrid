@extends('layouts.master')

@section('title', __('Hostel Allocations'))

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="card-title">{{ __('Hostel Allocations') }}</h4>
                            @can('hostel-allocation-create')
                                <button class="btn btn-theme btn-sm" data-toggle="modal" data-target="#createAllocationModal">
                                    <i class="fa fa-plus"></i> {{ __('Allocate Student') }}
                                </button>
                            @endcan
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="filter_hostel">{{ __('Filter by Hostel') }}</label>
                                <select id="filter_hostel" class="form-control">
                                    <option value="">{{ __('All Hostels') }}</option>
                                    @foreach($hostels as $h)
                                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_class">{{ __('Filter by Class') }}</label>
                                <select id="filter_class" class="form-control">
                                    <option value="">{{ __('All Classes') }}</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_status">{{ __('Filter by Status') }}</label>
                                <select id="filter_status" class="form-control">
                                    <option value="">{{ __('All Status') }}</option>
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="checked_out">{{ __('Checked Out') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <button class="btn btn-theme btn-block" onclick="refreshAllocationsTable()">
                                    <i class="fa fa-filter"></i> {{ __('Apply Filters') }}
                                </button>
                            </div>
                        </div>
                        
                        <table aria-describedby="mydesc" class='table' id='table_allocations' data-toggle="table"
                               data-url="{{ route('hostel.allocations.list') }}" data-click-to-select="true" 
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                               data-show-columns="true" data-show-refresh="true"
                               data-mobile-responsive="true" data-sort-name="allocation_date" data-sort-order="desc"
                               data-query-params="allocationQueryParams"
                               data-escape="false">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="student_name">{{ __('Student') }}</th>
                                    <th scope="col" data-field="student_email">{{ __('Email') }}</th>
                                    <th scope="col" data-field="class_name">{{ __('Class') }}</th>
                                    <th scope="col" data-field="hostel_name">{{ __('Hostel') }}</th>
                                    <th scope="col" data-field="room_number">{{ __('Room') }}</th>
                                    <th scope="col" data-field="bed_number">{{ __('Bed') }}</th>
                                    <th scope="col" data-field="allocation_date">{{ __('Allocation Date') }}</th>
                                    <th scope="col" data-field="checkout_date">{{ __('Checkout Date') }}</th>
                                    <th scope="col" data-field="status">{{ __('Status') }}</th>
                                    <th scope="col" data-field="operate" data-events="allocationEvents">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Allocation Modal -->
    <div class="modal fade" id="createAllocationModal" tabindex="-1" role="dialog" aria-labelledby="createAllocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createAllocationModalLabel">{{ __('Allocate Student to Hostel') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="createAllocationForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class_id">{{ __('Select Class') }} <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="class_id" name="class_id" required>
                                        <option value="">{{ __('Select Class') }}</option>
                                        @foreach($classes as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id">{{ __('Select Student') }} <span class="text-danger">*</span></label>
                                    <select class="form-control select2" id="student_id" name="student_id" required disabled>
                                        <option value="">{{ __('First select class') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="allocation_hostel_id">{{ __('Select Hostel') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" id="allocation_hostel_id" name="hostel_id" required>
                                        <option value="">{{ __('Select Hostel') }}</option>
                                        @foreach($hostels as $h)
                                            <option value="{{ $h->id }}">{{ $h->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_id">{{ __('Select Room') }} <span class="text-danger">*</span></label>
                                    <select class="form-control" id="room_id" name="room_id" required disabled>
                                        <option value="">{{ __('First select hostel') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="bed_number">{{ __('Bed Number') }}</label>
                                    <input type="text" class="form-control" id="bed_number" name="bed_number" placeholder="Optional">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="allocation_date">{{ __('Allocation Date') }} <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="allocation_date" name="allocation_date" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-theme">{{ __('Allocate') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function allocationQueryParams(params) {
            params.hostel_id = $('#filter_hostel').val();
            params.class_id = $('#filter_class').val();
            params.status = $('#filter_status').val();
            return params;
        }

        function refreshAllocationsTable() {
            $('#table_allocations').bootstrapTable('refresh');
        }

        window.allocationEvents = {
            'click .checkout-btn': function (e, value, row, index) {
                if (confirm('Are you sure you want to check out this student?')) {
                    $.ajax({
                        url: '{{ url("hostel/allocations/checkout") }}/' + row.id,
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                showSuccessToast(response.message);
                                $('#table_allocations').bootstrapTable('refresh');
                            } else {
                                showErrorToast(response.error);
                            }
                        },
                        error: function (xhr) {
                            showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                        }
                    });
                }
            },
            'click .delete-btn': function (e, value, row, index) {
                if (confirm('Are you sure you want to delete this allocation?')) {
                    $.ajax({
                        url: '{{ url("hostel/allocations") }}/' + row.id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (response) {
                            if (response.success) {
                                showSuccessToast(response.message);
                                $('#table_allocations').bootstrapTable('refresh');
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
            // Initialize select2
            $('.select2').select2();

            // Class change - load students
            $('#class_id').change(function () {
                var classId = $(this).val();
                var studentSelect = $('#student_id');
                
                studentSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);

                if (classId) {
                    $.ajax({
                        url: '{{ route("students.by.class") }}',
                        type: 'GET',
                        data: { class_id: classId },
                        success: function (response) {
                            studentSelect.empty().append('<option value="">{{ __("Select Student") }}</option>');
                            
                            if (response.data && response.data.length > 0) {
                                $.each(response.data, function (index, student) {
                                    studentSelect.append('<option value="' + student.user.id + '">' + 
                                        student.user.first_name + ' ' + student.user.last_name + '</option>');
                                });
                                studentSelect.prop('disabled', false);
                            } else {
                                studentSelect.append('<option value="">{{ __("No students found") }}</option>');
                            }
                        },
                        error: function () {
                            studentSelect.empty().append('<option value="">{{ __("Error loading students") }}</option>');
                        }
                    });
                } else {
                    studentSelect.empty().append('<option value="">{{ __("First select class") }}</option>').prop('disabled', true);
                }
            });

            // Hostel change - load available rooms
            $('#allocation_hostel_id').change(function () {
                var hostelId = $(this).val();
                var roomSelect = $('#room_id');
                
                roomSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);

                if (hostelId) {
                    $.ajax({
                        url: '{{ route("hostel.rooms.by.hostel") }}',
                        type: 'GET',
                        data: { hostel_id: hostelId },
                        success: function (response) {
                            roomSelect.empty().append('<option value="">{{ __("Select Room") }}</option>');
                            
                            if (response.length > 0) {
                                $.each(response, function (index, room) {
                                    roomSelect.append('<option value="' + room.id + '">' + room.text + '</option>');
                                });
                                roomSelect.prop('disabled', false);
                            } else {
                                roomSelect.append('<option value="">{{ __("No available rooms") }}</option>');
                            }
                        },
                        error: function () {
                            roomSelect.empty().append('<option value="">{{ __("Error loading rooms") }}</option>');
                        }
                    });
                } else {
                    roomSelect.empty().append('<option value="">{{ __("First select hostel") }}</option>').prop('disabled', true);
                }
            });

            // Create Allocation Form
            $('#createAllocationForm').on('submit', function (e) {
                e.preventDefault();
                $.ajax({
                    url: '{{ route("hostel.allocations.store") }}',
                    type: 'POST',
                    data: $(this).serialize(),
                    success: function (response) {
                        if (response.success) {
                            showSuccessToast(response.message);
                            $('#createAllocationModal').modal('hide');
                            $('#createAllocationForm')[0].reset();
                            $('#student_id').prop('disabled', true).empty().append('<option value="">{{ __("First select class") }}</option>');
                            $('#room_id').prop('disabled', true).empty().append('<option value="">{{ __("First select hostel") }}</option>');
                            $('#class_id').val('').trigger('change');
                            $('#allocation_hostel_id').val('').trigger('change');
                            $('#table_allocations').bootstrapTable('refresh');
                        } else {
                            showErrorToast(response.error);
                        }
                    },
                    error: function (xhr) {
                        showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                    }
                });
            });

            // Modal close reset
            $('#createAllocationModal').on('hidden.bs.modal', function () {
                $('#createAllocationForm')[0].reset();
                $('#student_id').prop('disabled', true).empty().append('<option value="">{{ __("First select class") }}</option>');
                $('#room_id').prop('disabled', true).empty().append('<option value="">{{ __("First select hostel") }}</option>');
                $('#class_id').val('').trigger('change');
                $('#allocation_hostel_id').val('').trigger('change');
            });
        });
    </script>
@endsection
