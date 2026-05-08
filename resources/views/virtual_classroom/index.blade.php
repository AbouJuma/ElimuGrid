@extends('layouts.master')

@section('title') Virtual Classroom @endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <i class="fas fa-video menu-icon"></i>
            Virtual Classroom
        </h3>
        @can('virtual-classroom-create')
            <div class="d-flex">
                <a href="{{ route('virtual-classroom.create') }}" class="btn btn-gradient-primary btn-sm">
                    <i class="fas fa-plus"></i> Create Session
                </a>
            </div>
        @endcan
    </div>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    {{-- Filter Section --}}
                    <form method="GET" action="{{ route('virtual-classroom.index') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="">All Status</option>
                                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                                        <option value="live" {{ request('status') == 'live' ? 'selected' : '' }}>Live</option>
                                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="d-flex">
                                        <button type="submit" class="btn btn-gradient-info btn-sm mr-2">
                                            <i class="fas fa-filter"></i> Filter
                                        </button>
                                        <a href="{{ route('virtual-classroom.index') }}" class="btn btn-gradient-secondary btn-sm">
                                            <i class="fas fa-undo"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    {{-- Sessions Table --}}
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Class/Section</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Schedule</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($virtualClassrooms as $session)
                                    <tr>
                                        <td>
                                            <strong>{{ $session->title }}</strong>
                                            @if($session->description)
                                                <br><small class="text-muted">{{ Str::limit($session->description, 50) }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $session->class->full_name ?? 'N/A' }}
                                            @if($session->section)
                                                <br><small class="text-muted">{{ $session->section->name }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $session->subject->name ?? 'N/A' }}</td>
                                        <td>{{ $session->teacher->full_name ?? 'N/A' }}</td>
                                        <td>
                                            {{ $session->start_time->format('M d, Y h:i A') }}
                                            <br><small class="text-muted">to {{ $session->end_time->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($session->status == 'scheduled')
                                                <span class="badge badge-warning">Scheduled</span>
                                                @if($session->isUpcoming())
                                                    <br><small class="text-muted">{{ $session->start_time->diffForHumans() }}</small>
                                                @endif
                                            @elseif($session->status == 'live')
                                                <span class="badge badge-success">Live</span>
                                                <span class="badge badge-danger pulse">●</span>
                                            @elseif($session->status == 'completed')
                                                <span class="badge badge-info">Completed</span>
                                                <br><small class="text-muted">{{ $session->attendance->count() }} attendees</small>
                                            @elseif($session->status == 'cancelled')
                                                <span class="badge badge-danger">Cancelled</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($session->isLive() || ($session->status == 'scheduled' && $session->start_time <= now()))
                                                    <a href="{{ route('virtual-classroom.join', $session->id) }}" class="btn btn-gradient-success btn-sm" target="_blank">
                                                        <i class="fas fa-sign-in-alt"></i> Join
                                                    </a>
                                            @endif

                                            @if(!in_array($session->status, ['completed', 'cancelled']))
                                                    <a href="{{ route('virtual-classroom.edit', $session->id) }}" class="btn btn-gradient-primary btn-sm">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </a>
                                                @endif

                                            @if($session->status != 'live')
                                                    <form action="{{ route('virtual-classroom.destroy', $session->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this session?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-gradient-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <i class="fas fa-video fa-3x text-muted mb-3"></i>
                                                <p>No virtual classroom sessions found.</p>
                                                @can('virtual-classroom-create')
                                                    <a href="{{ route('virtual-classroom.create') }}" class="btn btn-gradient-primary btn-sm">
                                                        Create your first session
                                                    </a>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="d-flex justify-content-end mt-3">
                        {{ $virtualClassrooms->links() }}
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

        // Auto-refresh for live sessions
        setInterval(function() {
            if ($('.badge-success:contains("Live")').length > 0) {
                // Don't auto-refresh to avoid interrupting user
            }
        }, 60000); // Check every minute
    });
</script>
@endsection
