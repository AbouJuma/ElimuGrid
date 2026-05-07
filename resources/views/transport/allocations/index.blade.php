@extends('layouts.master')

@section('title')
    {{ __('transport_allocations') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('transport_allocations') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3" id="toolbar">
                            <div class="form-group col-sm-12 col-md-2">
                                <select id="filter_route" class="form-control select2" data-placeholder="{{ __('select_route') }}">
                                    <option value="">{{ __('all_routes') }}</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}">{{ $route->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-2">
                                <select id="filter_class" class="form-control select2" data-placeholder="{{ __('select_class') }}">
                                    <option value="">{{ __('all_classes') }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-2">
                                <select id="filter_status" class="form-control select2">
                                    <option value="">{{ __('all_status') }}</option>
                                    <option value="active">{{ __('active') }}</option>
                                    <option value="terminated">{{ __('terminated') }}</option>
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-2">
                                <button id="generateFeesBtn" class="btn btn-primary btn-sm">
                                    <i class="fa fa-money"></i> {{ __('generate_fees') }}
                                </button>
                            </div>
                            <div class="form-group col-sm-12 col-md-4 text-right">
                                @can('transport-allocation-create')
                                    <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#allocationModal">
                                        <i class="fa fa-plus"></i> {{ __('add') }} {{ __('transport_allocation') }}
                                    </button>
                                @endcan
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('transport.allocations.list') }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="true" data-toolbar="#toolbar"
                                       data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                                       data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                                       data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                       data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                       data-export-options='{ "fileName": "transport-allocations-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                                       data-query-params="queryParams">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false" data-formatter="rowNumberFormatter">{{ __('no.') }}</th>
                                        <th scope="col" data-field="student_name" data-sortable="true">{{ __('student') }}</th>
                                        <th scope="col" data-field="class_name" data-sortable="true">{{ __('class') }}</th>
                                        <th scope="col" data-field="route_name" data-sortable="true">{{ __('route') }}</th>
                                        <th scope="col" data-field="stop_name" data-sortable="true">{{ __('stop') }}</th>
                                        <th scope="col" data-field="trip_type" data-sortable="true">{{ __('trip_type') }}</th>
                                        <th scope="col" data-field="fee_formatted" data-sortable="false">{{ __('fee') }}</th>
                                        <th scope="col" data-field="current_charge_status" data-sortable="false" data-formatter="statusFormatter">{{ __('status') }}</th>
                                        <th scope="col" data-field="allocation_date" data-sortable="true">{{ __('from') }}</th>
                                        <th scope="col" data-field="status" data-sortable="true">{{ __('status') }}</th>
                                        <th scope="col" data-field="operate" data-sortable="false" data-escape="false" data-formatter="operateFormatter" data-events="operateEvents">{{ __('action') }}</th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div class="modal fade" id="allocationModal" tabindex="-1" role="dialog" aria-labelledby="allocationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="allocationModalLabel">{{ __('add') }} {{ __('transport_allocation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="allocationForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="allocationId">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('select_class') }} <span class="text-danger">*</span></label>
                                <select name="class_id" id="classId" class="form-control select2" required>
                                    <option value="">{{ __('select') }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('select_student') }} <span class="text-danger">*</span></label>
                                <select name="student_id" id="studentId" class="form-control select2" required disabled>
                                    <option value="">{{ __('first_select_class') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('select_route') }} <span class="text-danger">*</span></label>
                                <select name="route_id" id="routeId" class="form-control select2" required>
                                    <option value="">{{ __('select') }}</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}" data-fee="{{ $route->activeFee?->amount ?? 0 }}">
                                            {{ $route->name }} ({{ $route->start_point }} - {{ $route->end_point }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('select_stop') }} <span class="text-danger">*</span></label>
                                <select name="stop_id" id="stopId" class="form-control select2" required disabled>
                                    <option value="">{{ __('first_select_route') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('trip_type') }} <span class="text-danger">*</span></label>
                                <select name="trip_type" class="form-control" required>
                                    <option value="morning">{{ __('morning_only') }}</option>
                                    <option value="evening">{{ __('evening_only') }}</option>
                                    <option value="both" selected>{{ __('both_ways') }}</option>
                                </select>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('allocation_date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="allocation_date" class="form-control" required value="{{ date('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="auto_charge" class="form-check-input" id="autoCharge" value="1" checked>
                                <label class="form-check-label" for="autoCharge">
                                    {{ __('auto_charge_transport_fee') }}
                                </label>
                            </div>
                        </div>
                        <div id="feeInfo" class="alert alert-info" style="display: none;">
                            <strong>{{ __('transport_fee') }}:</strong> <span id="feeAmount"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('close') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('save') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        function queryParams(p) {
            return {
                limit: p.limit,
                sort: p.sort,
                order: p.order,
                offset: p.offset,
                search: p.search,
                route_id: $('#filter_route').val(),
                class_id: $('#filter_class').val(),
                status: $('#filter_status').val()
            };
        }

        function statusFormatter(value, row, index) {
            if (value === 'paid') {
                return '<span class="badge badge-success">{{ __("paid") }}</span>';
            } else if (value === 'pending') {
                return '<span class="badge badge-warning">{{ __("pending") }}</span>';
            } else if (value === 'partial') {
                return '<span class="badge badge-info">{{ __("partial") }}</span>';
            }
            return '<span class="badge badge-secondary">' + value + '</span>';
        }

        function rowNumberFormatter(value, row, index) {
            // Get pagination offset from the table data
            var offset = row._offset || 0;
            return offset + index + 1;
        }

        function operateFormatter(value, row, index) {
            var html = '';
            // Mark as Paid button (only for pending status)
            if (row.current_charge_status === 'pending' || row.current_charge_status === 'Not charged') {
                html += '<button class="btn btn-sm btn-success mark-paid" title="Mark as Paid"><i class="fa fa-check"></i> Paid</button> ';
            }
            html += '<button class="btn btn-sm btn-primary edit-allocation" title="Edit"><i class="fa fa-edit"></i></button> ';
            html += '<button class="btn btn-sm btn-warning terminate-allocation" title="Terminate"><i class="fa fa-ban"></i></button> ';
            html += '<button class="btn btn-sm btn-danger delete-allocation" title="Delete"><i class="fa fa-trash"></i></button>';
            return html;
        }

        window.operateEvents = {
            'click .mark-paid': function (e, value, row, index) {
                if (confirm('{{ __("Mark this transport fee as paid?") }}')) {
                    $.ajax({
                        url: '{{ url("transport/fees/mark-paid") }}/' + row.id,
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (result) {
                            if (result.success) {
                                $('#table_list').bootstrapTable('refresh');
                                showSuccessToast(result.message);
                            } else {
                                showErrorToast(result.error);
                            }
                        },
                        error: function (xhr) {
                            showErrorToast(xhr.responseJSON?.error || 'Error marking as paid');
                        }
                    });
                }
            },
            'click .edit-allocation': function (e, value, row, index) {
                $('#allocationId').val(row.id);
                $('#allocationModalLabel').text('{{ __("edit") }} {{ __("transport_allocation") }}');
                
                // Load existing data
                $('#classId').val(row.class_id).trigger('change');
                $('#routeId').val(row.route_id).trigger('change');
                
                setTimeout(function() {
                    $('#studentId').val(row.student_id);
                    $('#stopId').val(row.stop_id);
                }, 500);
                
                $('#allocationModal').modal('show');
            },
            'click .terminate-allocation': function (e, value, row, index) {
                if (confirm('{{ __("Are you sure you want to terminate this allocation? Future fees will be cancelled.") }}')) {
                    $.ajax({
                        url: '{{ url("transport/allocations") }}/' + row.id + '/terminate',
                        type: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (result) {
                            if (result.success) {
                                $('#table_list').bootstrapTable('refresh');
                                showSuccessToast(result.message);
                            } else {
                                showErrorToast(result.error);
                            }
                        }
                    });
                }
            },
            'click .delete-allocation': function (e, value, row, index) {
                if (confirm('{{ __("Are you sure you want to delete this allocation?") }}')) {
                    $.ajax({
                        url: '{{ url("transport/allocations") }}/' + row.id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function (result) {
                            if (result.success) {
                                $('#table_list').bootstrapTable('refresh');
                                showSuccessToast(result.message);
                            } else {
                                showErrorToast(result.error);
                            }
                        }
                    });
                }
            }
        };

        $(document).ready(function () {
            // Filter changes
            $('#filter_route, #filter_class, #filter_status').on('change', function () {
                $('#table_list').bootstrapTable('refresh');
            });

            // Generate fees button
            $('#generateFeesBtn').on('click', function () {
                if (confirm('{{ __("Generate transport fees for current period?") }}')) {
                    $.ajax({
                        url: '{{ route("transport.fees.generate") }}',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            period: new Date().toISOString().slice(0, 7),
                            route_id: $('#filter_route').val()
                        },
                        success: function (result) {
                            if (result.success) {
                                showSuccessToast(result.message);
                                $('#table_list').bootstrapTable('refresh');
                            } else {
                                showErrorToast(result.error);
                            }
                        }
                    });
                }
            });

            // Class change - load students
            $('#classId').change(function () {
                var classId = $(this).val();
                var studentSelect = $('#studentId');
                
                studentSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);

                if (classId) {
                    $.ajax({
                        url: '{{ route("transport.students.available") }}',
                        type: 'GET',
                        data: { class_id: classId },
                        success: function (response) {
                            studentSelect.empty().append('<option value="">{{ __("Select Student") }}</option>');
                            
                            if (response.data && response.data.length > 0) {
                                $.each(response.data, function (index, student) {
                                    studentSelect.append('<option value="' + student.id + '">' + 
                                        student.full_name + ' (' + student.admission_no + ')</option>');
                                });
                                studentSelect.prop('disabled', false);
                            } else {
                                studentSelect.append('<option value="">{{ __("No students found") }}</option>');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error loading students:', error);
                            studentSelect.empty().append('<option value="">Error loading students</option>');
                            showErrorToast('Error loading students: ' + error);
                        }
                    });
                } else {
                    studentSelect.empty().append('<option value="">{{ __("First select class") }}</option>').prop('disabled', true);
                }
            });

            // Route change - load stops
            $('#routeId').change(function () {
                var routeId = $(this).val();
                var stopSelect = $('#stopId');
                var feeInfo = $('#feeInfo');
                var feeAmount = $('#feeAmount');
                
                // Show fee info
                var selectedOption = $(this).find('option:selected');
                var fee = parseFloat(selectedOption.data('fee')) || 0;
                if (fee > 0) {
                    feeAmount.text(fee.toFixed(2));
                    feeInfo.show();
                } else {
                    feeInfo.hide();
                }
                
                stopSelect.empty().append('<option value="">Loading...</option>').prop('disabled', true);

                if (routeId) {
                    $.ajax({
                        url: '{{ route("transport.stops.by.route") }}',
                        type: 'GET',
                        data: { route_id: routeId },
                        success: function (response) {
                            stopSelect.empty().append('<option value="">{{ __("Select Stop") }}</option>');
                            
                            if (response && response.length > 0) {
                                $.each(response, function (index, stop) {
                                    stopSelect.append('<option value="' + stop.id + '">' + stop.text + '</option>');
                                });
                                stopSelect.prop('disabled', false);
                            } else {
                                stopSelect.append('<option value="">{{ __("No stops found") }}</option>');
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error loading stops:', error);
                            stopSelect.empty().append('<option value="">Error loading stops</option>');
                            showErrorToast('Error loading stops: ' + error);
                        }
                    });
                } else {
                    stopSelect.empty().append('<option value="">{{ __("First select route") }}</option>').prop('disabled', true);
                }
            });

            // Form submission
            $('#allocationForm').on('submit', function (e) {
                e.preventDefault();
                var id = $('#allocationId').val();
                var url = id ? '{{ url("transport/allocations") }}/' + id : '{{ route("transport.allocations.store") }}';
                var method = id ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    type: method,
                    data: $(this).serialize(),
                    success: function (result) {
                        if (result.success) {
                            $('#allocationModal').modal('hide');
                            $('#table_list').bootstrapTable('refresh');
                            showSuccessToast(result.message);
                            $('#allocationForm')[0].reset();
                            $('#allocationId').val('');
                        } else {
                            showErrorToast(result.error);
                        }
                    },
                    error: function (xhr) {
                        showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                    }
                });
            });

            $('#allocationModal').on('hidden.bs.modal', function () {
                $('#allocationForm')[0].reset();
                $('#allocationId').val('');
                $('#studentId').prop('disabled', true).empty().append('<option value="">{{ __("First select class") }}</option>');
                $('#stopId').prop('disabled', true).empty().append('<option value="">{{ __("First select route") }}</option>');
                $('#feeInfo').hide();
                $('#allocationModalLabel').text('{{ __("add") }} {{ __("transport_allocation") }}');
            });
        });
    </script>
@endsection
