@extends('layouts.master')

@section('title')
    {{ __('unpaid_transport_fees') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('unpaid_transport_fees') }} {{ __('report') }}
            </h3>
        </div>
        <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <div class="row mb-3" id="toolbar">
                            <div class="form-group col-sm-12 col-md-3">
                                <select id="filter_route" class="form-control select2">
                                    <option value="">{{ __('all_routes') }}</option>
                                    @foreach($routes as $route)
                                        <option value="{{ $route->id }}">{{ $route->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-3">
                                <select id="filter_class" class="form-control select2">
                                    <option value="">{{ __('all_classes') }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('transport.reports.unpaid_fees.data') }}"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                       data-show-columns="true" data-show-refresh="true"
                                       data-export-data-type='all' data-show-export="true"
                                       data-query-params="queryParams"
                                       data-response-handler="responseHandler">
                                    <thead>
                                    <tr>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                        <th scope="col" data-field="no" data-sortable="false">{{ __('no.') }}</th>
                                        <th scope="col" data-field="student_name" data-sortable="true">{{ __('student') }}</th>
                                        <th scope="col" data-field="class_name" data-sortable="true">{{ __('class') }}</th>
                                        <th scope="col" data-field="route_name" data-sortable="true">{{ __('route') }}</th>
                                        <th scope="col" data-field="amount" data-sortable="true" data-formatter="amountFormatter">{{ __('amount') }}</th>
                                        <th scope="col" data-field="due_date" data-sortable="true">{{ __('due_date') }}</th>
                                        <th scope="col" data-field="days_overdue" data-sortable="true">{{ __('days_overdue') }}</th>
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
                class_id: $('#filter_class').val()
            };
        }

        function responseHandler(res) {
            if (res.summary) {
                $('#total_unpaid').text(res.summary.total_unpaid);
                $('#total_amount').text(res.summary.total_amount.toFixed(2));
                $('#overdue_count').text(res.summary.overdue_count);
                $('#overdue_amount').text(res.summary.overdue_amount.toFixed(2));
            }
            return res;
        }

        function amountFormatter(value, row, index) {
            return value ? parseFloat(value).toFixed(2) : '0.00';
        }

        $(document).ready(function () {
            $('#filter_route, #filter_class').on('change', function () {
                $('#table_list').bootstrapTable('refresh');
            });
        });
    </script>
@endsection
