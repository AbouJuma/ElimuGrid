@extends('layouts.app')

@section('title', __('create') . ' ' . __('schools'))

@section('content')
<div class="content-wrapper">
    <div class="content">
        <div class="col-lg-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">
                        {{ __('create') . ' ' . __('schools') }}
                    </h4>
                    
                    <form id="simplified-school-form" method="POST" action="{{ route('schools.simplified.store') }}">
                        @csrf
                        <div class="bg-light p-4 mt-4 mb-4">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="school_name">{{ __('name') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="school_name" id="school_name" placeholder="{{__('schools')}}" class="form-control" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="school_support_email">{{ __('school').' '.__('email') }} <span class="text-danger">*</span></label>
                                    <input type="email" name="school_support_email" id="school_support_email" placeholder="{{__('support').' '.__('email')}}" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="school_support_phone">{{ __('school').' '.__('phone') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="school_support_phone" maxlength="16" id="school_support_phone" placeholder="{{__('support').' '.__('phone')}}" class="form-control" required>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="school_tagline">{{ __('tagline')}} <span class="text-danger">*</span></label>
                                    <textarea name="school_tagline" id="school_tagline" cols="30" rows="3" class="form-control" placeholder="{{__('tagline')}}" required></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="school_address">{{ __('address')}} <span class="text-danger">*</span></label>
                                    <textarea name="school_address" id="school_address" cols="30" rows="3" class="form-control" placeholder="{{__('address')}}" required></textarea>
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label for="school_code_prefix">{{ __('School Code Prefix')}}</label> <span class="text-danger">*</span>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control school_code_prefix" id="school_code_prefix" name="school_code_prefix" required placeholder="{{ __('prefix') }}" value="SCH">
                                        <div class="input-group-append">
                                            <input type="text" class="input-group-text text-body school_code" id="basic-addon2" name="school_code" value="{{ date('Y') }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('domain').' '. __('type') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="domain_type" value="default" checked> {{ __('default') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="domain_type" value="custom"> {{ __('custom') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="form-group col-sm-12">
                                    <input class="btn btn-theme" type="submit" value="{{ __('submit') }}" />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    console.log('Simplified school form loaded');
    
    $('#simplified-school-form').on('submit', function(e) {
        e.preventDefault();
        console.log('Form submission started');
        
        // Show loading
        if (typeof showLoading === 'function') {
            showLoading();
        }
        
        var formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("schools.simplified.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                console.log('Success:', data);
                if (typeof closeLoading === 'function') {
                    closeLoading();
                }
                if (data.status === 'success') {
                    if (typeof showSuccessToast === 'function') {
                        showSuccessToast(data.message);
                    }
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    if (typeof showErrorToast === 'function') {
                        showErrorToast(data.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                console.log('Error:', error);
                console.log('Status:', status);
                console.log('XHR:', xhr);
                if (typeof closeLoading === 'function') {
                    closeLoading();
                }
                if (typeof showErrorToast === 'function') {
                    showErrorToast('Error occurred: ' + error);
                }
            }
        });
    });
});
</script>
@endsection
