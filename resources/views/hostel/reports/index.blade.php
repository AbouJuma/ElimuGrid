@extends('layouts.master')

@section('title', __('Hostel Reports'))

@section('content')
    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-2-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Total Hostels') }}</h5>
                        <h2>{{ $stats['total_hostels'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Total Rooms') }}</h5>
                        <h2>{{ $stats['total_rooms'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Total Capacity') }}</h5>
                        <h2>{{ $stats['total_capacity'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Occupied Beds') }}</h5>
                        <h2>{{ $stats['total_occupied'] }}</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-2-4">
                <div class="card bg-danger text-white">
                    <div class="card-body">
                        <h5 class="card-title">{{ __('Occupancy Rate') }}</h5>
                        <h2>{{ $stats['occupancy_rate'] }}%</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Occupancy Report -->
        <div class="row mt-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Room Occupancy Report') }}</h4>
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="report_hostel">{{ __('Filter by Hostel') }}</label>
                                <select id="report_hostel" class="form-control">
                                    <option value="">{{ __('All Hostels') }}</option>
                                    @foreach($hostels as $h)
                                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <button class="btn btn-theme btn-block" onclick="refreshOccupancyTable()">
                                    <i class="fa fa-filter"></i> {{ __('Apply Filter') }}
                                </button>
                            </div>
                        </div>
                        
                        <table aria-describedby="mydesc" class='table' id='table_occupancy' data-toggle="table"
                               data-url="{{ route('hostel.reports.occupancy') }}" data-click-to-select="true" 
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                               data-show-columns="true" data-show-refresh="true"
                               data-mobile-responsive="true" data-sort-name="hostel_name" data-sort-order="asc"
                               data-query-params="occupancyQueryParams"
                               data-escape="true">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="hostel_name">{{ __('Hostel') }}</th>
                                    <th scope="col" data-field="room_number">{{ __('Room Number') }}</th>
                                    <th scope="col" data-field="capacity">{{ __('Capacity') }}</th>
                                    <th scope="col" data-field="occupied_beds">{{ __('Occupied') }}</th>
                                    <th scope="col" data-field="available_beds">{{ __('Available') }}</th>
                                    <th scope="col" data-field="occupancy_rate">{{ __('Occupancy Rate') }}</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Allocation List -->
        <div class="row mt-4">
            <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">{{ __('Student Allocation List') }}</h4>
                        
                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label for="list_hostel">{{ __('Filter by Hostel') }}</label>
                                <select id="list_hostel" class="form-control">
                                    <option value="">{{ __('All Hostels') }}</option>
                                    @foreach($hostels as $h)
                                        <option value="{{ $h->id }}">{{ $h->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="list_class">{{ __('Filter by Class') }}</label>
                                <select id="list_class" class="form-control">
                                    <option value="">{{ __('All Classes') }}</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>&nbsp;</label>
                                <button class="btn btn-theme btn-block" onclick="refreshAllocationsTable()">
                                    <i class="fa fa-filter"></i> {{ __('Apply Filters') }}
                                </button>
                            </div>
                        </div>
                        
                        <table aria-describedby="mydesc" class='table' id='table_allocation_list' data-toggle="table"
                               data-url="{{ route('hostel.allocations.list') }}" data-click-to-select="true" 
                               data-side-pagination="server" data-pagination="true"
                               data-page-list="[5, 10, 20, 50, 100]" data-search="true"
                               data-show-columns="true" data-show-refresh="true"
                               data-mobile-responsive="true" data-sort-name="allocation_date" data-sort-order="desc"
                               data-query-params="allocationListQueryParams"
                               data-escape="false">
                            <thead>
                                <tr>
                                    <th scope="col" data-field="student_name">{{ __('Student') }}</th>
                                    <th scope="col" data-field="student_email">{{ __('Email') }}</th>
                                    <th scope="col" data-field="class_name">{{ __('Class') }}</th>
                                    <th scope="col" data-field="hostel_name">{{ __('Hostel') }}</th>
                                    <th scope="col" data-field="room_number">{{ __('Room') }}</th>
                                    <th scope="col" data-field="allocation_date">{{ __('Allocation Date') }}</th>
                                    <th scope="col" data-field="status">{{ __('Status') }}</th>
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
        function occupancyQueryParams(params) {
            params.hostel_id = $('#report_hostel').val();
            return params;
        }

        function allocationListQueryParams(params) {
            params.hostel_id = $('#list_hostel').val();
            params.class_id = $('#list_class').val();
            return params;
        }

        function refreshOccupancyTable() {
            $('#table_occupancy').bootstrapTable('refresh');
        }

        function refreshAllocationsTable() {
            $('#table_allocation_list').bootstrapTable('refresh');
        }

        $(document).ready(function () {
            // Initialize tables on load
            $('#table_occupancy').bootstrapTable('refresh');
            $('#table_allocation_list').bootstrapTable('refresh');
        });
    </script>
@endsection
