@extends('layouts.master')

@section('title') Virtual Classroom Reports @endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <i class="fas fa-chart-bar menu-icon"></i>
            Virtual Classroom Reports
        </h3>
    </div>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    {{-- Filter Section --}}
                    <form method="GET" action="{{ route('virtual-classroom.reports') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Class</label>
                                    <select name="class_id" class="form-control select2">
                                        <option value="">All Classes</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ request('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Teacher</label>
                                    <select name="teacher_id" class="form-control select2">
                                        <option value="">All Teachers</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ request('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="d-flex">
                                        <button type="submit" class="btn btn-gradient-info btn-sm mr-2">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="{{ route('virtual-classroom.reports') }}" class="btn btn-gradient-secondary btn-sm">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-gradient-success btn-sm" onclick="window.print();">
                                        <i class="fas fa-print"></i> Print Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Summary Cards --}}
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-gradient-info text-white">
                                <div class="card-body">
                                    <h5>Total Sessions</h5>
                                    <h3>{{ $sessions->total() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-gradient-success text-white">
                                <div class="card-body">
                                    <h5>Completed</h5>
                                    <h3>{{ $sessions->where('status', 'completed')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-gradient-warning text-white">
                                <div class="card-body">
                                    <h5>Scheduled</h5>
                                    <h3>{{ $sessions->where('status', 'scheduled')->count() }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-gradient-primary text-white">
                                <div class="card-body">
                                    <h5>Total Attendees</h5>
                                    <h3>{{ $sessions->sum(function($s) { return $s->attendance->count(); }) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sessions Table with Attendance --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Session</th>
                                    <th>Class/Subject</th>
                                    <th>Teacher</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th>Attendance</th>
                                    <th>Avg Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sessions as $session)
                                    <tr>
                                        <td>
                                            <strong>{{ $session->title }}</strong>
                                            @if($session->description)
                                                <br><small class="text-muted">{{ Str::limit($session->description, 30) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $session->class->name ?? 'N/A' }}
                                            @if($session->section)
                                                <br><small class="text-muted">{{ $session->section->name }}</small>
                                            @endif
                                            <br><small class="text-info">{{ $session->subject->name ?? 'N/A' }}</small>
                                        </td>
                                        <td>{{ $session->teacher->full_name ?? 'N/A' }}</td>
                                        <td>
                                            {{ $session->start_time->format('M d, Y h:i A') }}
                                            <br><small class="text-muted">{{ $session->start_time->diffInMinutes($session->end_time) }} minutes</small>
                                        </td>
                                        <td>
                                            @if($session->status == 'scheduled')
                                                <span class="badge badge-warning">Scheduled</span>
                                            @elseif($session->status == 'live')
                                                <span class="badge badge-success">Live</span>
                                            @elseif($session->status == 'completed')
                                                <span class="badge badge-info">Completed</span>
                                            @elseif($session->status == 'cancelled')
                                                <span class="badge badge-danger">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-primary">{{ $session->attendance->count() }} students</span>
                                            @if($session->attendance->count() > 0)
                                                <button class="btn btn-sm btn-outline-info ml-2" type="button" data-toggle="collapse" data-target="#attendance-{{ $session->id }}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            @endif
                                        </td>
                                        <td>
                                            @if($session->attendance->count() > 0)
                                                @php
                                                    $avgDuration = $session->attendance->avg('duration');
                                                    echo round($avgDuration, 1) . ' min';
                                                @endphp
                                            @else
                                                - min
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Attendance Details --}}
                                    @if($session->attendance->count() > 0)
                                        <tr class="collapse" id="attendance-{{ $session->id }}">
                                            <td colspan="7" class="bg-light">
                                                <h6 class="mb-3">Attendance Details</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered">
                                                        <thead class="thead-light">
                                                            <tr>
                                                                <th>Student</th>
                                                                <th>Joined At</th>
                                                                <th>Left At</th>
                                                                <th>Duration</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($session->attendance as $attendance)
                                                                <tr>
                                                                    <td>{{ $attendance->student->user->full_name ?? 'N/A' }}</td>
                                                                    <td>{{ $attendance->joined_at ? $attendance->joined_at->format('h:i A') : '-' }}</td>
                                                                    <td>{{ $attendance->left_at ? $attendance->left_at->format('h:i A') : '-' }}</td>
                                                                    <td>{{ $attendance->duration_formatted }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                                <p>No virtual classroom sessions found for the selected filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-end mt-3">
                        {{ $sessions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
