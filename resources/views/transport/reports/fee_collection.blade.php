@extends('layouts.master')

@section('title')
    {{ __('transport_fee_collection') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('transport_fee_collection') }} {{ __('report') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3" id="toolbar">
                            <div class="form-group col-sm-12 col-md-3">
                                <select id="filter_route" class="form-control select2" data-placeholder="{{ __('select_route') }}">
                                    <option value="">{{ __('all_routes') }}</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}">{{ $route->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <input type="month" id="filter_period" class="form-control" value="{{ date('Y-m') }}">
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <select id="filter_status" class="form-control select2">
                                    <option value="">{{ __('all_status') }}</option>
                                    <option value="paid">{{ __('paid') }}</option>
                                    <option value="pending">{{ __('pending') }}</option>
                                    <option value="partial">{{ __('partial') }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('transport.reports.fee_collection.data') }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[5, 10, 20, 50, 100, 200,All]" data-search="true" data-toolbar="#toolbar"
                                       data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                                       data-fixed-number="2" data-fixed-right-number="1" data-trim-on-search="false"
                                       data-mobile-responsive="true" data-sort-name="id" data-sort-order="desc"
                                       data-maintain-selected="true" data-export-data-type='all' data-show-export="true"
                                       data-export-options='{ "fileName": "transport-fee-collection-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}'
                                       data-query-params="queryParams">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="student_name" data-sortable="true">{{ __('student') }}</th>
                                        <th scope="col" data-field="class_name" data-sortable="true">{{ __('class') }}</th>
                                        <th scope="col" data-field="route_name" data-sortable="true">{{ __('route') }}</th>
                                        <th scope="col" data-field="period" data-sortable="true">{{ __('period') }}</th>
                                        <th scope="col" data-field="amount" data-sortable="true" data-formatter="amountFormatter">{{ __('amount') }}</th>
                                        <th scope="col" data-field="paid_amount" data-sortable="true" data-formatter="amountFormatter">{{ __('paid') }}</th>
                                        <th scope="col" data-field="balance" data-sortable="true" data-formatter="amountFormatter">{{ __('balance') }}</th>
                                        <th scope="col" data-field="due_date" data-sortable="true">{{ __('due_date') }}</th>
                                        <th scope="col" data-field="status" data-sortable="true" data-formatter="statusFormatter">{{ __('status') }}</th>
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
                period: $('#filter_period').val(),
                status: $('#filter_status').val()
            };
        }

        function amountFormatter(value, row, index) {
            return value ? parseFloat(value).toFixed(2) : '0.00';
        }

        function statusFormatter(value, row, index) {
            if (value === 'paid') {
                return '<span class="badge badge-success">{{ __("paid") }}</span>';
            } else if (value === 'pending') {
                return '<span class="badge badge-warning">{{ __("pending") }}</span>';
            } else if (value === 'partial') {
                return '<span class="badge badge-info">{{ __("partial") }}</span>';
            } else if (value === 'cancelled') {
                return '<span class="badge badge-secondary">{{ __("cancelled") }}</span>';
            }
            return '<span class="badge badge-secondary">' + value + '</span>';
        }

        $(document).ready(function () {
            $('#filter_route, #filter_period, #filter_status').on('change', function () {
                $('#table_list').bootstrapTable('refresh');
            });
        });
    </script>
@endsection
