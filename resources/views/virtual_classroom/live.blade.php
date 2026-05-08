@extends('layouts.master')

@section('title') Live Virtual Classroom - {{ $virtualClassroom->title }} @endsection

@section('css')
<style>
    .meeting-container {
        height: calc(100vh - 200px);
        min-height: 500px;
        position: relative;
    }
    #jitsi-meeting-container {
        width: 100%;
        height: 100%;
        border-radius: 8px;
        overflow: hidden;
    }
    .session-info-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
    }
    .moderator-badge {
        background: #28a745;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .participant-badge {
        background: #17a2b8;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper">
    {{-- Session Header --}}
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card session-info-card">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1">{{ $virtualClassroom->title }}</h4>
                            <p class="mb-0">
                                <i class="fas fa-book"></i> {{ $virtualClassroom->subject->name ?? 'N/A' }} |
                                <i class="fas fa-graduation-cap"></i> {{ $virtualClassroom->class->name ?? 'N/A' }}
                                @if($virtualClassroom->section)
                                    - {{ $virtualClassroom->section->name }}
                                @endif
                            </p>
                        </div>
                        <div class="text-right">
                            @if($isModerator)
                                <span class="moderator-badge"><i class="fas fa-user-shield"></i> Moderator</span>
                            @else
                                <span class="participant-badge"><i class="fas fa-user"></i> Participant</span>
                            @endif
                            <br>
                            <small class="text-white-50">
                                {{ $meetingTimes['start_formatted'] }} - {{ $meetingTimes['end_formatted'] }}
                                ({{ $meetingTimes['duration_minutes'] }} min)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Meeting Container --}}
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="meeting-container">
                        <div id="jitsi-meeting-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Leave Button --}}
    <div class="row mt-3">
        <div class="col-md-12 text-center">
            <form method="POST" action="{{ route('virtual-classroom.leave', $virtualClassroom->id) }}" id="leaveForm">
                @csrf
                <button type="submit" class="btn btn-gradient-danger btn-lg" onclick="return confirm('Are you sure you want to leave this session?');">
                    <i class="fas fa-sign-out-alt"></i> Leave Session
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="https://meet.jit.si/external_api.js"></script>
<script>
    var api = null;
    var meetingConfig = @json($meetingConfig);

    function initJitsi() {
        try {
            const domain = 'meet.jit.si';
            const options = {
                roomName: meetingConfig.roomName,
                width: meetingConfig.width,
                height: meetingConfig.height,
                parentNode: document.querySelector('#' + meetingConfig.parentNode),
                configOverwrite: meetingConfig.configOverwrite,
                interfaceConfigOverwrite: meetingConfig.interfaceConfigOverwrite,
                userInfo: meetingConfig.userInfo
            };

            // Add JWT if available
            if (meetingConfig.jwt) {
                options.jwt = meetingConfig.jwt;
            }

            api = new JitsiMeetExternalAPI(domain, options);

            // Handle participant joined event
            api.addEventListener('participantJoined', function(event) {
                console.log('Participant joined:', event);
            });

            // Handle participant left event
            api.addEventListener('participantLeft', function(event) {
                console.log('Participant left:', event);
            });

            // Handle video conference left event
            api.addEventListener('videoConferenceLeft', function(event) {
                console.log('Video conference left:', event);
                handleMeetingClose();
            });

            // Handle ready to close event
            api.addEventListener('readyToClose', function(event) {
                handleMeetingClose();
            });

            console.log('Jitsi meeting initialized successfully');
        } catch (error) {
            console.error('Failed to initialize Jitsi:', error);
            document.getElementById('jitsi-meeting-container').innerHTML = 
                '<div class="alert alert-danger">' +
                '<h5>Failed to load meeting interface</h5>' +
                '<p>There was an error loading the Jitsi meeting interface.</p>' +
                '<p><strong>Error:</strong> ' + error.message + '</p>' +
                '<p>Please try refreshing the page or contact support.</p>' +
                '</div>';
        }
    }

    function handleMeetingClose() {
        // Dispose the Jitsi API
        if (api) {
            api.dispose();
        }

        // Submit leave form
        document.getElementById('leaveForm').submit();
    }

    // Initialize Jitsi when page loads
    $(document).ready(function() {
        if (typeof JitsiMeetExternalAPI !== 'undefined') {
            initJitsi();
        } else {
            console.error('Jitsi Meet External API not loaded');
            alert('Failed to load meeting interface. Please check your internet connection.');
        }
    });

    // Handle page unload (browser close/refresh)
    $(window).on('beforeunload', function() {
        if (api) {
            api.dispose();
        }
    });
</script>
@endsection
