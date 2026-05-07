@extends('layouts.master')

@section('title')
    {{ __('students') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">
                {{ __('manage') . ' ' . __('students') }}
            </h3>
        </div>

        <div class="row">
            <div class="col-lg-12 grid-margin stretch-card">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            {{ __('create') . ' ' . __('students') }}
                        </h4>
                        <form class="pt-3 student-registration-form" id="create-form" data-success-function="formSuccessFunction" enctype="multipart/form-data" action="{{ route('students.store') }}" method="POST" novalidate="novalidate">
                            @csrf
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-3">
                                    <label>{{ __('Gr Number') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('admission_no', $admission_no, ['readonly','placeholder' => __('Gr Number'), 'class' => 'form-control']) !!}
                                    <div class="mt-2">
                                        <small class="text-muted">{{ __('This will be used as barcode for attendance scanning') }}</small>
                                        <div class="barcode-display mt-1 p-2 bg-light text-center">
                                            <svg class="barcode" id="gr-barcode">
                                                <!-- Barcode will be generated here -->
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Student ID Card Preview -->
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-3">
                                    <label>{{ __('Student ID Card Preview') }}</label>
                                    <div class="student-id-card" id="studentIdCard">
                                        <div class="id-card-header">
                                            <div class="school-logo">
                                                <i class="fas fa-school"></i>
                                            </div>
                                            <div class="school-name">{{ Auth::user()->school->name ?? 'School Name' }}</div>
                                        </div>
                                        <div class="id-card-body">
                                            <div class="student-photo">
                                                <i class="fas fa-user-circle"></i>
                                            </div>
                                            <div class="student-info">
                                                <div class="info-row">
                                                    <span class="label">Name:</span>
                                                    <span class="value" id="idCardName">Student Name</span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="label">Class:</span>
                                                    <span class="value" id="idCardClass">-</span>
                                                </div>
                                                <div class="info-row">
                                                    <span class="label">GR No:</span>
                                                    <span class="value" id="idCardGr">{{ $admission_no }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="id-card-footer">
                                            <div class="barcode-section">
                                                <div class="barcode id-barcode" id="idCardBarcode"></div>
                                                <div class="barcode-number" id="idCardBarcodeNumber">{{ $admission_no }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-center">
                                        <button type="button" class="btn btn-primary btn-sm" onclick="printIdCard()">
                                            <i class="fas fa-print"></i> {{ __('Print ID Card') }}
                                        </button>
                                    </div>
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-3">
                                    <label for="class_section">{{ __('class_section') }} <span class="text-danger">*</span></label>
                                    <select name="class_section_id" id="class_section" class="form-control select2">
                                        <option value="">{{ __('select') . ' ' . __('Class') . ' ' . __('section') }}</option>
                                        @if(count($class_sections))
                                            @foreach ($class_sections as $class_section)
                                                <option value="{{ $class_section->id }}">{{$class_section->full_name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-3">
                                    <label for="session_year_id">{{ __('session_year') }} <span class="text-danger">*</span></label>
                                    <select name="session_year_id" id="session_year_id" class="form-control select2">
                                        @if(count($sessionYears))
                                            @foreach ($sessionYears as $year)
                                                <option value="{{ $year->id }}" {{$year->default==1 ? "selected" : ""}}>{{$year->name}}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-3">
                                    <label>{{ __('admission_date') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('admission_date', null, ['placeholder' => __('admission_date'), 'class' => 'datepicker-popup-no-future form-control','id'=>'admission_date','autocomplete'=>'off']) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>


                                @if(!empty($features) )
                                    <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                        <label>{{ __('Status') }} <span class="text-danger">*</span></label><br>
                                        <div class="d-flex">
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('status', 1) !!}
                                                    {{ __('Active') }}
                                                </label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <label class="form-check-label">
                                                    {!! Form::radio('status', 0,true) !!}
                                                    {{ __('Inactive') }}
                                                </label>
                                            </div>
                                        </div>
                                        <span class="text-danger small">{{ __('Note').':-'.__('Activating this will consider in your current subscription cycle') }}</span>
                                    </div>
                                @endif
                            </div>
                            <hr>
                            <div class="row mt-5">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('first_name', null, ['placeholder' => __('first_name'), 'class' => 'form-control']) !!}

                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('last_name', null, ['placeholder' => __('last_name'), 'class' => 'form-control']) !!}

                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('dob', null, ['placeholder' => __('dob'), 'class' => 'datepicker-popup-no-future form-control','autocomplete'=>'off']) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'male',true) !!}
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'female') !!}
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label for="image">{{ __('image') }} </label>
                                    <input type="file" name="image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" id="image" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('mobile') }}</label>
                                    {!! Form::number('mobile', null, ['placeholder' => __('mobile'), 'min' => 0 ,'class' => 'form-control remove-number-increment']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('current_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('current_address', null, ['required', 'placeholder' => __('current_address'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('permanent_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('permanent_address', null, ['required', 'placeholder' => __('permanent_address'), 'class' => 'form-control', 'rows' => 3]) !!}
                                </div>
                            </div>

                            @if(count($extraFields))
                                <div class="row other-details">

                                    {{-- Loop the FormData --}}
                                    @foreach ($extraFields as $key => $data)
                                            {{-- Edit Extra Details ID --}}
                                            {{ Form::hidden('extra_fields['.$key.'][id]', '', ['id' => $data->type.'_'.$key.'_id']) }}

                                            {{-- Form Field ID --}}
                                            {{ Form::hidden('extra_fields['.$key.'][form_field_id]', $data->id, ['id' => $data->type.'_'.$key.'_id']) }}

                                            <div class='form-group col-md-12 col-lg-6 col-xl-4 col-sm-12'>

                                                {{-- Add lable to all the elements excluding checkbox --}}
                                                @if($data->type != 'radio' && $data->type != 'checkbox')
                                                    <label>{{$data->name}} @if($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif</label>
                                                @endif

                                                {{-- Text Field --}}
                                                @if($data->type == 'text')
                                                    {{ Form::text('extra_fields['.$key.'][data]', '', ['class' => 'form-control text-fields', 'id' => $data->type.'_'.$key, 'placeholder' => $data->name, ($data->is_required == 1 ? 'required' : '')]) }}
                                                    {{-- Number Field --}}
                                                @elseif($data->type == 'number')
                                                    {{ Form::number('extra_fields['.$key.'][data]', '', ['min' => 0, 'class' => 'form-control number-fields', 'id' => $data->type.'_'.$key, 'placeholder' => $data->name, ($data->is_required == 1 ? 'required' : '')]) }}

                                                    {{-- Dropdown Field --}}
                                                @elseif($data->type == 'dropdown')
                                                    {{ Form::select('extra_fields['.$key.'][data]',$data->default_values,null,
                                                        ['id' => $data->type.'_'.$key,'class' => 'form-control select-fields',
                                                            ($data->is_required == 1 ? 'required' : ''),
                                                            'placeholder' => 'Select '.$data->name
                                                        ]
                                                    )}}

                                                        {{-- Radio Field --}}
                                                    @elseif($data->type == 'radio')
                                                        <label class="d-block">{{$data->name}} @if($data->is_required)
                                                                <span class="text-danger">*</span>
                                                            @endif</label>
                                                        <div class="row col-md-12 col-lg-12 col-xl-6 col-sm-12">
                                                            @if(count($data->default_values))
                                                                @foreach ($data->default_values as $keyRadio => $value)
                                                                    <div class="form-check mr-2">
                                                                        <label class="form-check-label">
                                                                            {{ Form::radio('extra_fields['.$key.'][data]', $value, null, ['id' => $data->type.'_'.$keyRadio, 'class' => 'radio-fields',($data->is_required == 1 ? 'required' : '')]) }}
                                                                            {{$value}}
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                        </div>

                                                        {{-- Checkbox Field --}}
                                                    @elseif($data->type == 'checkbox')
                                                        <label class="d-block">{{$data->name}} @if($data->is_required)
                                                                <span class="text-danger">*</span>
                                                            @endif</label>
                                                        @if(count($data->default_values))
                                                            <div class="row col-lg-12 col-xl-6 col-md-12 col-sm-12">
                                                                @foreach ($data->default_values as $chkKey => $value)
                                                                    <div class="mr-2 form-check">
                                                                        <label class="form-check-label">
                                                                            {{ Form::checkbox('extra_fields['.$key.'][data][]', $value, null, ['id' => $data->type.'_'.$chkKey, 'class' => 'form-check-input chkclass checkbox-fields',($data->is_required == 1 ? 'required' : '')]) }} {{ $value }}

                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        {{-- Textarea Field --}}
                                                    @elseif($data->type == 'textarea')
                                                        {{ Form::textarea('extra_fields['.$key.'][data]', '', ['placeholder' => $data->name, 'id' => $data->type.'_'.$key, 'class' => 'form-control textarea-fields', ($data->is_required ? 'required' : '') , 'rows' => 3]) }}

                                                        {{-- File Upload Field --}}
                                                    @elseif($data->type == 'file')
                                                        <div class="input-group col-xs-12">
                                                            {{ Form::file('extra_fields['.$key.'][data]', ['class' => 'file-upload-default', 'id' => $data->type.'_'.$key, ($data->is_required ? 'required' : '')]) }}
                                                            {{ Form::text('', '', ['class' => 'form-control file-upload-info', 'disabled' => '', 'placeholder' => __('image')]) }}
                                                            <span class="input-group-append">
                                                                <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                                            </span>
                                                        </div>
                                                        <div id="file_div_{{$key}}" class="mt-2 d-none file-div">
                                                            <a href="" id="file_link_{{$key}}" target="_blank">{{$data->name}}</a>
                                                        </div>

                                                    @endif
                                                </div>
                                    @endforeach
                                </div>
                            @endif

                            <hr>
                            {{-- Guardian Details --}}
                            <div class="row mt-5">
                                <div class="form-group col-sm-12 col-md-12">
                                    <label for="guardian_email">{{ __('guardian') . ' ' . __('email') }} <span class="text-danger">*</span></label>
                                    <select class="guardian-search form-control guardian_email" id="guardian_email"></select>
                                    <input type="hidden" id="guardian_email" class="guardian_email" name="guardian_email">
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('guardian') . ' ' . __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('guardian_first_name', null, ['placeholder' => __('guardian') . ' ' . __('first_name'), 'class' => 'form-control', 'id' => 'guardian_first_name']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('guardian') . ' ' . __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('guardian_last_name', null, ['placeholder' => __('guardian') . ' ' . __('last_name'), 'class' => 'form-control', 'id' => 'guardian_last_name']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('guardian') . ' ' . __('mobile') }} <span class="text-danger">*</span></label>
                                    {!! Form::number('guardian_mobile', null, ['placeholder' => __('guardian') . ' ' . __('mobile'), 'class' => 'form-control remove-number-increment', 'id' => 'guardian_mobile','min' => 1 ]) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-12">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" checked name="guardian_gender" value="male" id="guardian_male">
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                <input type="radio" name="guardian_gender" value="female" id="guardian_female">
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-4 col-lg-6 col-xl-4">
                                    <label for="guardian_image">{{ __('image') }} </label>
                                    <input type="file" name="guardian_image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" id="guardian_image" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    <img id="guardian-image-preview" src="" alt="Guardian Image" class="img-fluid w-25"/>
                                </div>
                            </div>
                            <input class="btn btn-theme float-right ml-3" id="create-btn" type="submit" value={{ __('submit') }}>
                            <input class="btn btn-secondary float-right" type="reset" value={{ __('reset') }}>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

<style>
/* Student ID Card Styles */
.student-id-card {
    width: 250px;
    height: 380px;
    border: 2px solid #333;
    border-radius: 10px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    margin: 0 auto;
}
.id-card-header {
    background: #fff;
    padding: 12px;
    text-align: center;
    border-bottom: 2px solid #333;
}
.school-logo {
    font-size: 24px;
    color: #667eea;
    margin-bottom: 3px;
}
.school-name {
    font-size: 11px;
    font-weight: bold;
    color: #333;
    text-transform: uppercase;
    line-height: 1.2;
}
.id-card-body {
    flex: 1;
    padding: 15px;
    display: flex;
    flex-direction: column;
    align-items: center;
    color: #fff;
}
.student-photo {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 12px;
    border: 3px solid #fff;
}
.student-photo i {
    font-size: 45px;
    color: #667eea;
}
.student-info {
    width: 100%;
}
.info-row {
    display: flex;
    margin-bottom: 6px;
    font-size: 12px;
}
.info-row .label {
    font-weight: bold;
    min-width: 50px;
    color: #fff;
}
.info-row .value {
    color: #fff;
    flex: 1;
}
.id-card-footer {
    background: #fff;
    padding: 10px 8px 8px 8px;
    text-align: center;
}
.barcode-section {
    display: flex;
    flex-direction: column;
    align-items: center;
}
.id-barcode {
    height: 45px;
    width: 100%;
    margin-bottom: 4px;
}
.barcode-number {
    font-family: monospace;
    font-size: 12px;
    color: #000;
    font-weight: bold;
    letter-spacing: 3px;
}
.barcode-lines {
    display: flex;
    justify-content: center;
    align-items: flex-end;
    height: 45px;
}
</style>
@endsection
@section('script')
    <script>
        function formSuccessFunction() {
            setTimeout(() => {
                window.location.reload()
            }, 3000);
        }

        // Generate barcode when GR number is entered
        $('#admission_no').on('input', function() {
            var grNumber = $(this).val();
            if (grNumber.length > 0) {
                generateBarcode(grNumber);
            } else {
                $('#gr-barcode').empty();
            }
        });

        function generateBarcode(text) {
            // Simple barcode generation using CSS
            var barcode = $('#gr-barcode');
            barcode.empty();
            
            // Create barcode pattern (simple representation)
            var patterns = {
                '0': '101', '1': '111', '2': '110', '3': '100', '4': '001',
                '5': '010', '6': '011', '7': '000', '8': '010', '9': '101'
            };
            
            var barcodeHtml = '<div class="barcode-text">' + text + '</div><div class="barcode-lines">';
            
            for (var i = 0; i < text.length; i++) {
                var char = text[i];
                var pattern = patterns[char] || '101';
                
                for (var j = 0; j < pattern.length; j++) {
                    var width = pattern[j] === '1' ? '3px' : '1px';
                    var height = '50px';
                    barcodeHtml += '<div style="display:inline-block;width:' + width + ';height:' + height + ';background:#000;margin:0 1px;"></div>';
                }
            }
            
            barcodeHtml += '</div>';
            barcode.html(barcodeHtml);
        }

        // Generate Code 128 barcode using JsBarcode library
        function generateIdCardBarcode(text) {
            if (!text) return;
            
            // Clear previous barcode
            $('#idCardBarcode').empty();
            
            // Create a canvas element
            var canvas = document.createElement('canvas');
            $('#idCardBarcode').append(canvas);
            
            // Generate Code 128 barcode
            try {
                JsBarcode(canvas, text, {
                    format: 'CODE128',
                    width: 1.5,
                    height: 25,
                    displayValue: false,
                    margin: 10,
                    fontSize: 0
                });
            } catch(e) {
                console.error('Barcode generation error:', e);
            }
        }

        // Update ID card preview
        function updateIdCard() {
            var firstName = $('input[name="first_name"]').val() || '';
            var lastName = $('input[name="last_name"]').val() || '';
            var fullName = (firstName + ' ' + lastName).trim() || 'Student Name';
            var grNumber = $('#admission_no').val() || '{{ $admission_no }}';
            var className = $('#class_section option:selected').text() || '-';
            
            $('#idCardName').text(fullName);
            $('#idCardClass').text(className);
            $('#idCardGr').text(grNumber);
            $('#idCardBarcodeNumber').text(grNumber);
            generateIdCardBarcode(grNumber);
        }

        // Print ID card
        function printIdCard() {
            // Get the canvas barcode data
            var originalBarcode = document.getElementById('idCardBarcode');
            var canvas = originalBarcode.querySelector('canvas');
            var barcodeDataUrl = canvas ? canvas.toDataURL('image/png') : '';
            
            // Clone the ID card
            var originalCard = document.getElementById('studentIdCard');
            var clonedCard = originalCard.cloneNode(true);
            
            // Replace the barcode div with an image
            var clonedBarcode = clonedCard.querySelector('#idCardBarcode');
            if (clonedBarcode && barcodeDataUrl) {
                clonedBarcode.innerHTML = '<img src="' + barcodeDataUrl + '" style="width:100%;height:25px;"/>';
            }
            
            var printContent = clonedCard.outerHTML;
            var printWindow = window.open('', '_blank', 'width=400,height=600');
            printWindow.document.write('<html><head><title>Student ID Card</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body{margin:20px;display:flex;justify-content:center;align-items:center;font-family:Arial,sans-serif}');
            printWindow.document.write('.student-id-card{width:250px;height:380px;border:2px solid #000;border-radius:10px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 4px 8px rgba(0,0,0,0.2)}');
            printWindow.document.write('.id-card-header{background:#fff;padding:12px;text-align:center;border-bottom:2px solid #000}');
            printWindow.document.write('.school-logo{font-size:24px;color:#667eea;margin-bottom:3px}');
            printWindow.document.write('.school-name{font-size:11px;font-weight:bold;color:#000;text-transform:uppercase}');
            printWindow.document.write('.id-card-body{flex:1;padding:15px;display:flex;flex-direction:column;align-items:center;color:#fff}');
            printWindow.document.write('.student-photo{width:70px;height:70px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;margin-bottom:12px;border:3px solid #fff;font-size:45px;color:#667eea}');
            printWindow.document.write('.student-info{width:100%}');
            printWindow.document.write('.info-row{display:flex;margin-bottom:8px;font-size:13px}');
            printWindow.document.write('.info-row .label{font-weight:bold;min-width:50px;color:#fff}');
            printWindow.document.write('.info-row .value{color:#fff;flex:1}');
            printWindow.document.write('.id-card-footer{background:#fff;padding:10px 8px 8px 8px;text-align:center}');
            printWindow.document.write('.barcode-section{display:flex;flex-direction:column;align-items:center}');
            printWindow.document.write('.id-barcode{height:25px;width:100%;margin-bottom:4px}');
            printWindow.document.write('.id-barcode svg{width:100%;height:25px}');
            printWindow.document.write('.id-barcode rect{fill:#000}');
            printWindow.document.write('.barcode-number{font-family:monospace;font-size:9px;color:#000;font-weight:bold;letter-spacing:1px}');
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(printContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function() {
                printWindow.print();
            }, 300);
        }

        // Update ID card when fields change
        $('input[name="first_name"], input[name="last_name"]').on('input', updateIdCard);
        $('#class_section').on('change', updateIdCard);
        
        // Initialize ID card
        $(document).ready(function() {
            updateIdCard();
        });

        $('#admission_date').datepicker({
            format: "dd-mm-yyyy",
            rtl: isRTL()
        }).datepicker("setDate", 'now');
    </script>
@endsection
