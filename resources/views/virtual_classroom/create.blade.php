@extends('layouts.master')

@section('title') Create Virtual Classroom Session @endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <i class="fas fa-video menu-icon"></i>
            Create Virtual Classroom Session
        </h3>
        <a href="{{ route('virtual-classroom.index') }}" class="btn btn-gradient-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Sessions
        </a>
    </div>

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card mx-auto">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('virtual-classroom.store') }}" id="createSessionForm">
                        @csrf

                        {{-- Basic Information --}}
                        <div class="form-group">
                            <label for="title">Session Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title') }}"
                                   placeholder="Enter session title" required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="Enter session description">{{ old('description') }}</textarea>
                            @error('description')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Class & Section --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="class_id">Class <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('class_id') is-invalid @enderror"
                                            id="class_id" name="class_id" required>
                                        <option value="">Select Class</option>
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}" {{ old('class_id') == $class->id ? 'selected' : '' }}>
                                                {{ $class->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('class_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="section_id">Section</label>
                                    <select class="form-control select2 @error('section_id') is-invalid @enderror"
                                            id="section_id" name="section_id">
                                        <option value="">Select Section (Optional)</option>
                                        @if(isset($sections) && count($sections))
                                            @foreach($sections as $section)
                                                <option value="{{ $section->id }}" {{ old('section_id') == $section->id ? 'selected' : '' }}>
                                                    {{ $section->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('section_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Subject & Teacher --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="subject_id">Subject <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('subject_id') is-invalid @enderror"
                                            id="subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        @if(isset($subjects) && count($subjects))
                                            @foreach($subjects as $subject)
                                                <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                                    {{ $subject->name }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('subject_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="teacher_id">Teacher <span class="text-danger">*</span></label>
                                    <select class="form-control select2 @error('teacher_id') is-invalid @enderror"
                                            id="teacher_id" name="teacher_id" required>
                                        <option value="">Select Teacher</option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ old('teacher_id') == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('teacher_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Schedule --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="start_time">Start Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control @error('start_time') is-invalid @enderror"
                                           id="start_time" name="start_time" value="{{ old('start_time') }}" required>
                                    @error('start_time')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_time">End Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror"
                                           id="end_time" name="end_time" value="{{ old('end_time') }}" required>
                                    @error('end_time')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-gradient-primary mr-2">
                                <i class="fas fa-save"></i> Create Session
                            </button>
                            <a href="{{ route('virtual-classroom.index') }}" class="btn btn-gradient-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2();

        // Load sections when class changes
        $('#class_id').on('change', function() {
            var classId = $(this).val();
            console.log('Class changed to:', classId);

            if (classId) {
                // Load sections
                $.ajax({
                    url: '{{ route("virtual-classroom.get-sections") }}',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(data) {
                        console.log('Loaded sections successfully:', data);
                        $('#section_id').empty().append('<option value="">Select Section (Optional)</option>');
                        $.each(data, function(key, section) {
                            $('#section_id').append('<option value="' + section.id + '">' + section.name + '</option>');
                        });
                        $('#section_id').trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to load sections:', status, error, xhr.responseText);
                    }
                });

                // Load subjects
                $.ajax({
                    url: '{{ route("virtual-classroom.get-subjects") }}',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(data) {
                        console.log('Loaded subjects successfully:', data);
                        $('#subject_id').empty().append('<option value="">Select Subject</option>');
                        $.each(data, function(key, subject) {
                            $('#subject_id').append('<option value="' + subject.id + '">' + subject.name + '</option>');
                        });
                        $('#subject_id').trigger('change');
                    },
                    error: function(xhr, status, error) {
                        console.error('Failed to load subjects:', status, error, xhr.responseText);
                    }
                });
            } else {
                $('#section_id').empty().append('<option value="">Select Section (Optional)</option>').trigger('change');
                $('#subject_id').empty().append('<option value="">Select Subject</option>').trigger('change');
            }
        });

        // Set minimum datetime to now
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        $('#start_time').attr('min', now.toISOString().slice(0, 16));
        $('#end_time').attr('min', now.toISOString().slice(0, 16));

        // Update end time minimum when start time changes
        $('#start_time').on('change', function() {
            var startTime = $(this).val();
            $('#end_time').attr('min', startTime);
        });
    });
</script>
@endsection
