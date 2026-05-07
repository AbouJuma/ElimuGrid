@extends('layouts.master')

@section('title')
    {{ __('transport_routes') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('transport_routes') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3" id="toolbar">
                            <div class="col-12 text-right">
                                @can('transport-route-create')
                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#routeModal">
                                        <i class="fa fa-plus"></i> {{ __('add') }} {{ __('transport_route') }}
                                    </button>
                                @endcan
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('transport.routes.list') }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="true" data-toolbar="#toolbar"
                                       data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                                       data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                                       data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                       data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                       data-export-options='{ "fileName": "transport-routes-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                                       data-query-params="queryParams">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="name" data-sortable="true">{{ __('name') }}</th>
                                        <th scope="col" data-field="start_point" data-sortable="true">{{ __('start_point') }}</th>
                                        <th scope="col" data-field="end_point" data-sortable="true">{{ __('end_point') }}</th>
                                        <th scope="col" data-field="distance_km" data-sortable="true">{{ __('distance') }} (km)</th>
                                        <th scope="col" data-field="fee_formatted" data-sortable="false">{{ __('fee') }}</th>
                                        <th scope="col" data-field="stops_count" data-sortable="true">{{ __('stops') }}</th>
                                        <th scope="col" data-field="allocations_count" data-sortable="true">{{ __('students') }}</th>
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
    <div class="modal fade" id="routeModal" tabindex="-1" role="dialog" aria-labelledby="routeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="routeModalLabel">{{ __('add') }} {{ __('transport_route') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="routeForm" method="POST">
                    @csrf
                    <div class="modal-body">
                        <input type="hidden" name="id" id="routeId">
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('name') }} <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" id="routeName" required placeholder="e.g., Route A - North Zone">
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('distance') }} (km)</label>
                                <input type="number" name="distance_km" class="form-control" id="routeDistance" step="0.01" min="0">
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('start_point') }} <span class="text-danger">*</span></label>
                                <input type="text" name="start_point" class="form-control" id="startPoint" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('end_point') }} <span class="text-danger">*</span></label>
                                <input type="text" name="end_point" class="form-control" id="endPoint" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label>{{ __('departure_time') }}</label>
                                <input type="time" name="departure_time" class="form-control" id="departureTime">
                            </div>
                            <div class="form-group col-md-6">
                                <label>{{ __('return_time') }}</label>
                                <input type="time" name="return_time" class="form-control" id="returnTime">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('description') }}</label>
                            <textarea name="description" class="form-control" id="routeDescription" rows="2"></textarea>
                        </div>
                        
                        <hr>
                        <h6 class="text-primary">{{ __('transport_fee') }} {{ __('settings') }}</h6>
                        <div class="row">
                            <div class="form-group col-md-4">
                                <label>{{ __('amount') }} <span class="text-danger">*</span></label>
                                <input type="number" name="fee_amount" class="form-control" id="feeAmount" step="0.01" min="0" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ __('billing_cycle') }} <span class="text-danger">*</span></label>
                                <select name="billing_cycle" class="form-control" id="billingCycle" required>
                                    <option value="monthly">{{ __('monthly') }}</option>
                                    <option value="term">{{ __('term') }}</option>
                                    <option value="yearly">{{ __('yearly') }}</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>{{ __('effective_from') }} <span class="text-danger">*</span></label>
                                <input type="date" name="fee_effective_from" class="form-control" id="feeEffectiveFrom" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>{{ __('fee_description') }}</label>
                            <textarea name="fee_description" class="form-control" id="feeDescription" rows="2"></textarea>
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
                search: p.search
            };
        }

        function operateFormatter(value, row, index) {
            var html = '';
            html += '<button class="btn btn-sm btn-info manage-stops" title="Manage Stops"><i class="fa fa-map-marker"></i></button> ';
            html += '<button class="btn btn-sm btn-primary edit-route" title="Edit"><i class="fa fa-edit"></i></button> ';
            html += '<button class="btn btn-sm btn-danger delete-route" title="Delete"><i class="fa fa-trash"></i></button>';
            return html;
        }

        window.operateEvents = {
            'click .edit-route': function (e, value, row, index) {
                $('#routeId').val(row.id);
                $('#routeName').val(row.name);
                $('#routeDistance').val(row.distance_km);
                $('#startPoint').val(row.start_point);
                $('#endPoint').val(row.end_point);
                $('#departureTime').val(row.departure_time);
                $('#returnTime').val(row.return_time);
                $('#routeDescription').val(row.description);
                $('#feeAmount').val(row.current_fee);
                
                $('#routeModalLabel').text('{{ __("edit") }} {{ __("transport_route") }}');
                $('#routeModal').modal('show');
            },
            'click .manage-stops': function (e, value, row, index) {
                $('#stopsRouteId').val(row.id);
                $('#stopsRouteName').text(row.name);
                loadStops(row.id);
                $('#stopsModal').modal('show');
            },
            'click .delete-route': function (e, value, row, index) {
                if (confirm('{{ __("Are you sure you want to delete this route?") }}')) {
                    $.ajax({
                        url: '{{ url("transport/routes") }}/' + row.id,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function (result) {
                            if (result.success) {
                                $('#table_list').bootstrapTable('refresh');
                                showSuccessToast(result.message);
                            } else {
                                showErrorToast(result.error);
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
            // Set default effective from date
            $('#feeEffectiveFrom').val(new Date().toISOString().split('T')[0]);

            $('#routeForm').on('submit', function (e) {
                e.preventDefault();
                var id = $('#routeId').val();
                var url = id ? '{{ url("transport/routes") }}/' + id : '{{ route("transport.routes.store") }}';
                var method = id ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    type: method,
                    data: $(this).serialize(),
                    success: function (result) {
                        if (result.success) {
                            $('#routeModal').modal('hide');
                            $('#table_list').bootstrapTable('refresh');
                            showSuccessToast(result.message);
                            $('#routeForm')[0].reset();
                            $('#routeId').val('');
                        } else {
                            showErrorToast(result.error);
                        }
                    },
                    error: function (xhr) {
                        showErrorToast(xhr.responseJSON?.error || 'An error occurred');
                    }
                });
            });

            $('#routeModal').on('hidden.bs.modal', function () {
                $('#routeForm')[0].reset();
                $('#routeId').val('');
                $('#routeModalLabel').text('{{ __("add") }} {{ __("transport_route") }}');
                $('#feeEffectiveFrom').val(new Date().toISOString().split('T')[0]);
            });
        });

        function loadStops(routeId) {
            $.ajax({
                url: '{{ route("transport.stops.by.route") }}',
                type: 'GET',
                data: { route_id: routeId },
                success: function (response) {
                    var html = '';
                    if (response && response.length > 0) {
                        html = '<table class="table table-sm"><thead><tr><th>Name</th><th>Pickup</th><th>Drop</th></tr></thead><tbody>';
                        $.each(response, function (i, stop) {
                            html += '<tr><td>' + stop.name + '</td><td>' + (stop.pickup_time || '-') + '</td><td>' + (stop.drop_time || '-') + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    } else {
                        html = '<div class="alert alert-info">No stops added yet. Add stops below.</div>';
                    }
                    $('#stopsList').html(html);
                },
                error: function (xhr) {
                    var errorMsg = xhr.responseJSON?.error || 'Error loading stops';
                    $('#stopsList').html('<div class="alert alert-danger">Error: ' + errorMsg + '</div>');
                    console.error('Stops load error:', xhr.responseJSON);
                }
            });
        }

        // Use event delegation for modal buttons
        $(document).on('click', '#addStopBtn', function (e) {
            e.preventDefault();
            console.log('Add Stop button clicked via delegation');
            
            var routeId = $('#stopsRouteId').val();
            var stopName = $('#stopName').val().trim();
            
            console.log('Route ID:', routeId, 'Stop Name:', stopName);
            
            if (!stopName) {
                showErrorToast('Please enter a stop name');
                return;
            }
            
            var data = {
                _token: '{{ csrf_token() }}',
                route_id: routeId,
                name: stopName,
                pickup_time: $('#stopPickup').val(),
                drop_time: $('#stopDrop').val()
            };
            
            console.log('Sending data:', data);

            $.ajax({
                url: '{{ url("transport/stops") }}',
                type: 'POST',
                data: data,
                success: function (result) {
                    console.log('Success:', result);
                    if (result.success) {
                        showSuccessToast('Stop added successfully');
                        $('#stopName').val('');
                        $('#stopPickup').val('');
                        $('#stopDrop').val('');
                        loadStops(routeId);
                    } else {
                        showErrorToast(result.error);
                    }
                },
                error: function (xhr) {
                    var errorMsg = xhr.responseJSON?.error || 'Error adding stop';
                    showErrorToast(errorMsg);
                    console.error('Add stop error:', xhr.responseJSON);
                }
            });
        });
    </script>

    <!-- Stops Management Modal -->
    <div class="modal fade" id="stopsModal" tabindex="-1" role="dialog" aria-labelledby="stopsModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="stopsModalLabel">Manage Stops - <span id="stopsRouteName"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="stopsRouteId">
                    <div id="stopsList"></div>
                    <hr>
                    <h6>Add New Stop</h6>
                    <div class="form-group">
                        <label>Stop Name</label>
                        <input type="text" id="stopName" class="form-control" placeholder="e.g., Bus Stop A">
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label>Pickup Time</label>
                            <input type="time" id="stopPickup" class="form-control">
                        </div>
                        <div class="form-group col-md-6">
                            <label>Drop Time</label>
                            <input type="time" id="stopDrop" class="form-control">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" id="addStopBtn" class="btn btn-primary">Add Stop</button>
                </div>
            </div>
        </div>
    </div>
@endsection
