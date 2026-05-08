@extends('layouts.master')

@section('title') Edit Virtual Classroom Session @endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title">
            <i class="fas fa-video menu-icon"></i>
            Edit Virtual Classroom Session
        </h3>
        <a href="{{ route('virtual-classroom.index') }}" class="btn btn-gradient-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Back to Sessions
        </a>
    </div>

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card mx-auto">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('virtual-classroom.update', $virtualClassroom->id) }}" id="editSessionForm">
                        @csrf
                        @method('PUT')

                        {{-- Basic Information --}}
                        <div class="form-group">
                            <label for="title">Session Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror"
                                   id="title" name="title" value="{{ old('title', $virtualClassroom->title) }}"
                                   placeholder="Enter session title" required>
                            @error('title')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3"
                                      placeholder="Enter session description">{{ old('description', $virtualClassroom->description) }}</textarea>
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
                                            <option value="{{ $class->id }}" {{ old('class_id', $virtualClassroom->class_id) == $class->id ? 'selected' : '' }}>
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
                                        @foreach($sections as $section)
                                            <option value="{{ $section->id }}" {{ old('section_id', $virtualClassroom->section_id) == $section->id ? 'selected' : '' }}>
                                                {{ $section->name }}
                                            </option>
                                        @endforeach
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
                                        @foreach($subjects as $subject)
                                            <option value="{{ $subject->id }}" {{ old('subject_id', $virtualClassroom->subject_id) == $subject->id ? 'selected' : '' }}>
                                                {{ $subject->name }}
                                            </option>
                                        @endforeach
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
                                            <option value="{{ $teacher->id }}" {{ old('teacher_id', $virtualClassroom->teacher_id) == $teacher->id ? 'selected' : '' }}>
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
                                           id="start_time" name="start_time"
                                           value="{{ old('start_time', $virtualClassroom->start_time->format('Y-m-d\TH:i')) }}"
                                           required>
                                    @error('start_time')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="end_time">End Time <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control @error('end_time') is-invalid @enderror"
                                           id="end_time" name="end_time"
                                           value="{{ old('end_time', $virtualClassroom->end_time->format('Y-m-d\TH:i')) }}"
                                           required>
                                    @error('end_time')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-gradient-primary mr-2">
                                <i class="fas fa-save"></i> Update Session
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

        // Load sections and subjects when class changes
        $('#class_id').on('change', function() {
            var classId = $(this).val();

            if (classId) {
                // Load sections
                $.ajax({
                    url: '{{ route("virtual-classroom.get-sections") }}',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(data) {
                        var currentSection = $('#section_id').val();
                        $('#section_id').empty().append('<option value="">Select Section (Optional)</option>');
                        $.each(data, function(key, section) {
                            var selected = (currentSection == section.id) ? 'selected' : '';
                            $('#section_id').append('<option value="' + section.id + '" ' + selected + '>' + section.name + '</option>');
                        });
                    }
                });

                // Load subjects
                $.ajax({
                    url: '{{ route("virtual-classroom.get-subjects") }}',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(data) {
                        var currentSubject = $('#subject_id').val();
                        $('#subject_id').empty().append('<option value="">Select Subject</option>');
                        $.each(data, function(key, subject) {
                            var selected = (currentSubject == subject.id) ? 'selected' : '';
                            $('#subject_id').append('<option value="' + subject.id + '" ' + selected + '>' + subject.name + '</option>');
                        });
                    }
                });
            }
        });

        // Update end time minimum when start time changes
        $('#start_time').on('change', function() {
            var startTime = $(this).val();
            $('#end_time').attr('min', startTime);
        });
    });
</script>
@endsection
