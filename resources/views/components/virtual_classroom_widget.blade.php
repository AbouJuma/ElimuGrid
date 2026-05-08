{{-- Virtual Classroom Dashboard Widget --}}
@canany(['virtual-classroom-list', 'virtual-classroom-join'])
    @if(app('App\Services\FeaturesService')::hasFeature('Virtual Classroom'))
        <div class="card mb-4" id="virtual-classroom-widget">
            <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-video"></i> Virtual Classroom</h5>
                <a href="{{ route('virtual-classroom.index') }}" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                {{-- Live Sessions Alert --}}
                <div id="live-sessions-alert" class="d-none">
                    <div class="alert alert-success m-3 mb-0">
                        <i class="fas fa-circle text-danger pulse"></i>
                        <strong>Live Now!</strong> You have active sessions running.
                        <a href="{{ route('virtual-classroom.index', ['status' => 'live']) }}" class="btn btn-sm btn-success float-right">Join Now</a>
                    </div>
                </div>

                {{-- Upcoming Sessions --}}
                <div class="p-3" id="upcoming-sessions-container">
                    <h6 class="text-muted mb-3">Upcoming Sessions</h6>
                    <div id="upcoming-sessions-list">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @section('js')
            @parent
            <script>
                $(document).ready(function() {
                    // Load upcoming sessions
                    loadUpcomingSessions();

                    // Refresh every 5 minutes
                    setInterval(loadUpcomingSessions, 300000);
                });

                function loadUpcomingSessions() {
                    $.ajax({
                        url: '{{ route("virtual-classroom.upcoming") }}',
                        type: 'GET',
                        success: function(data) {
                            renderUpcomingSessions(data);
                        },
                        error: function() {
                            $('#upcoming-sessions-list').html('<p class="text-muted text-center">Failed to load sessions</p>');
                        }
                    });

                    // Check for live sessions
                    $.ajax({
                        url: '{{ route("virtual-classroom.live") }}',
                        type: 'GET',
                        success: function(data) {
                            if (data && data.length > 0) {
                                $('#live-sessions-alert').removeClass('d-none');
                            } else {
                                $('#live-sessions-alert').addClass('d-none');
                            }
                        }
                    });
                }

                function renderUpcomingSessions(sessions) {
                    var html = '';

                    if (sessions && sessions.length > 0) {
                        html += '<div class="list-group list-group-flush">';
                        $.each(sessions.slice(0, 5), function(index, session) {
                            var startTime = new Date(session.start_time);
                            var now = new Date();
                            var diff = Math.floor((startTime - now) / (1000 * 60)); // minutes

                            var timeBadge = '';
                            if (diff < 0) {
                                timeBadge = '<span class="badge badge-success">Live Now</span>';
                            } else if (diff < 60) {
                                timeBadge = '<span class="badge badge-warning">in ' + diff + ' min</span>';
                            } else {
                                timeBadge = '<span class="badge badge-info">' + startTime.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) + '</span>';
                            }

                            html += '<a href="{{ url("virtual-classroom") }}/' + session.id + '/join" class="list-group-item list-group-item-action">';
                            html += '<div class="d-flex w-100 justify-content-between">';
                            html += '<h6 class="mb-1">' + session.title + '</h6>';
                            html += timeBadge;
                            html += '</div>';
                            html += '<p class="mb-1 text-muted">';
                            if (session.subject) {
                                html += '<i class="fas fa-book"></i> ' + session.subject.name + ' ';
                            }
                            if (session.class) {
                                html += '<i class="fas fa-graduation-cap"></i> ' + session.class.name;
                            }
                            html += '</p>';
                            html += '<small>';
                            if (session.teacher) {
                                html += 'By ' + session.teacher.full_name;
                            }
                            html += '</small>';
                            html += '</a>';
                        });
                        html += '</div>';
                    } else {
                        html += '<div class="text-center py-4">';
                        html += '<i class="fas fa-video-slash fa-2x text-muted mb-2"></i>';
                        html += '<p class="text-muted mb-0">No upcoming sessions</p>';
                        html += '</div>';
                    }

                    $('#upcoming-sessions-list').html(html);
                }
            </script>
        @endsection
    @endif
@endcanany
