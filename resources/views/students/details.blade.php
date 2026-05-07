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
                            {{ __('list') . ' ' . __('students') }}
                        </h4>
                        <div class="row" id="toolbar">
                            <div class="form-group col-sm-12 col-md-4">
                                <label class="filter-menu">{{ __('Class Section') }} <span class="text-danger">*</span></label>
                                <select name="filter_class_section_id" id="filter_class_section_id" class="form-control">
                                    <option value="">{{ __('select_class_section') }}</option>
                                    @foreach ($class_sections as $class_section)
                                        <option value={{ $class_section->id }}>{{$class_section->full_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-sm-12 col-md-4">
                                <label class="filter-menu">{{ __('Session Year') }} <span class="text-danger">*</span></label>
                                <select name="filter_session_year_id" id="filter_session_year_id" class="form-control">
                                    @foreach ($sessionYears as $sessionYear)
                                        <option value={{ $sessionYear->id }} {{$sessionYear->default==1?"selected":""}}>{{$sessionYear->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            @can('student-delete')
                                <div class="form-group col-12">
                                    <button id="update-status" class="btn btn-secondary" disabled><span class="update-status-btn-name">{{ __('Inactive') }}</span></button>
                                </div>
                            @endcan
                        </div>

                        @can('student-delete')
                            <div class="col-12 mt-4 text-right">
                                <b><a href="#" class="table-list-type active mr-2" data-id="0">{{__('active')}}</a></b> | <a href="#" class="ml-2 table-list-type" data-id="1">{{__("Inactive")}}</a>
                            </div>
                        @endcan
                        <div class="row">
                            <div class="col-12">
                                <table aria-describedby="mydesc" class='table' id='table_list'
                                       data-toggle="table" data-url="{{ route('students.show',[1]) }}" data-click-to-select="true"
                                       data-side-pagination="server" data-pagination="true"
                                       data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                                       data-toolbar="#toolbar" data-show-columns="true" data-show-refresh="true" data-fixed-columns="false"
                                       data-trim-on-search="false" data-mobile-responsive="true" data-sort-name="id"
                                       data-sort-order="desc" data-maintain-selected="true" data-export-types="['pdf','json', 'xml', 'csv', 'txt', 'sql', 'doc', 'excel']" data-show-export="true"
                                       data-export-options='{ "fileName": "students-list-<?= date('d-m-y') ?>" ,"ignoreColumn": ["operate"]}' data-query-params="studentDetailsQueryParams"
                                       data-check-on-init="true" data-escape="true">
                                    <thead>
                                    <tr>
                                        <th data-field="state" data-checkbox="true"></th>
                                        <th scope="col" data-field="id" data-sortable="true" data-visible="false">{{ __('id') }}</th>
                                        <th scope="col" data-field="no">{{ __('no.') }}</th>
                                        <th scope="col" data-field="user.id" data-visible="false">{{ __('User Id') }}</th>
                                        <th scope="col" data-field="user.full_name">{{ __('name') }}</th>
                                        <th scope="col" data-field="user.dob" data-formatter="dateFormatter">{{ __('dob') }}</th>
                                        <th scope="col" data-field="user.image" data-formatter="imageFormatter">{{ __('image') }}</th>
                                        <th scope="col" data-field="class_section.full_name">{{ __('class_section') }}</th>
                                        <th scope="col" data-field="admission_no"> {{ __('Gr Number') }}</th>
                                        <th scope="col" data-field="roll_number">{{ __('roll_no') }}</th>
                                        <th scope="col" data-field="user.gender">{{ __('gender') }}</th>
                                        <th scope="col" data-field="admission_date" data-formatter="dateFormatter">{{ __('admission_date') }}</th>
                                        <th scope="col" data-field="guardian.email">{{ __('guardian') . ' ' . __('email') }}</th>
                                        <th scope="col" data-field="guardian.full_name">{{ __('guardian') . ' ' . __('name') }}</th>
                                        <th scope="col" data-field="guardian.mobile">{{ __('guardian') . ' ' . __('mobile') }}</th>
                                        <th scope="col" data-field="guardian.gender">{{ __('guardian') . ' ' . __('gender') }}</th>

                                        {{-- Admission form fields --}}
                                        @foreach ($extraFields as $field)
                                            <th scope="col" data-visible="false" data-escape="false" data-field="{{ $field->name }}">{{ $field->name }}</th>
                                        @endforeach
                                        {{-- End admission form fields --}}

                                        @canany(['student-edit','student-delete'])
                                            <th data-events="studentEvents" class="align-button text-center" scope="col" data-field="operate" data-escape="false">{{ __('action') }}</th>
                                        @endcanany
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

    @can('student-edit')
        <div class="modal fade" id="editModal" data-backdrop="static" tabindex="-1" role="dialog"
             aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="exampleModalLabel">{{ __('edit') . ' ' . __('students') }}</h4><br>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fa fa-close"></i></span>
                        </button>
                    </div>
                    <form id="edit-form" class="edit-student-registration-form" novalidate="novalidate" action="{{ url('students') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('admission_no') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('admission_no', null, ['placeholder' => __('admission_no'), 'class' => 'form-control', 'id' => 'edit_admission_no' ,'readonly'=>true]) !!}

                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Session Year') }} <span class="text-danger">*</span></label>
                                    <select required name="session_year_id" class="form-control" id="session_year_id">
                                        @foreach ($sessionYears as $sessionYear)
                                            <option value="{{ $sessionYear->id }}">{{$sessionYear->name}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('Class Section') }} <span class="text-danger">*</span></label>
                                    <select required name="class_section_id" class="form-control" id="edit_student_class_section_id">
                                        <option value="">{{ __('select_class_section') }}</option>
                                        @foreach ($class_sections as $class_section)
                                            <option value={{ $class_section->id }}>{{$class_section->full_name}}</option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                            <hr>
                            <div class="row mt-5">
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('first_name', null, ['placeholder' => __('first_name'), 'class' => 'form-control', 'id' => 'edit_first_name']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('last_name', null, ['placeholder' => __('last_name'), 'class' => 'form-control', 'id' => 'edit_last_name']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('dob') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('dob', null, ['placeholder' => __('dob'), 'class' => 'datepicker-popup-no-future form-control', 'id' => 'edit_dob']) !!}
                                    <span class="input-group-addon input-group-append">
                                    </span>
                                </div>

                                <div class="form-group col-sm-12 col-md-4">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'male', false ,['id' => 'male']) !!}
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('gender', 'female', false , ['id' => 'female']) !!}
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('image') }} </label>
                                    <input type="file" name="image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" required="required" id="edit_image"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    <div style="width: 100px;">
                                        <img src="" id="edit-student-image-tag" class="img-fluid w-100" alt=""/>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('mobile') }}</label>
                                    {!! Form::number('mobile', null, ['placeholder' => __('mobile'), 'min' => 1 , 'class' => 'form-control remove-number-increment', 'id' => 'edit_mobile']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('current_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('current_address', null, ['required', 'placeholder' => __('current_address'), 'class' => 'form-control', 'rows' => 3,'id'=>'edit-current-address']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-6">
                                    <label>{{ __('permanent_address') }} <span class="text-danger">*</span></label>
                                    {!! Form::textarea('permanent_address', null, ['required', 'placeholder' => __('permanent_address'), 'class' => 'form-control', 'rows' => 3,'id'=>'edit-permanent-address']) !!}
                                </div>
                            </div>

                            @if(!empty($extraFields))
                                <div class="row other-details">

                                    {{-- Loop the FormData --}}
                                    @foreach ($extraFields as $key => $data)
                                        @if($data->user_type == 1)
                                            @php $fieldName = str_replace(' ', '_', $data->name) @endphp
                                            {{-- Edit Extra Details ID --}}
                                            {{ Form::hidden('extra_fields['.$key.'][id]', '', ['id' => $fieldName.'_id']) }}

                                            {{-- Form Field ID --}}
                                            {{ Form::hidden('extra_fields['.$key.'][form_field_id]', $data->id) }}

                                            {{-- FormFieldType --}}
                                            {{ Form::hidden('extra_fields['.$key.'][input_type]', $data->type) }}

                                            <div class='form-group col-md-12 col-lg-6 col-xl-4 col-sm-12'>

                                                {{-- Add lable to all the elements excluding checkbox --}}
                                                @if($data->type != 'radio' && $data->type != 'checkbox')
                                                    <label>{{$data->name}} @if($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif</label>
                                                @endif

                                                {{-- Text Field --}}
                                                @if($data->type == 'text')
                                                    {{ Form::text('extra_fields['.$key.'][data]', '', ['class' => 'form-control text-fields', 'id' => $fieldName, 'placeholder' => $data->name, ($data->is_required == 1 ? 'required' : '')]) }}
                                                    {{-- Number Field --}}
                                                @elseif($data->type == 'number')
                                                    {{ Form::number('extra_fields['.$key.'][data]', '', ['min' => 0, 'class' => 'form-control number-fields', 'id' => $fieldName, 'placeholder' => $data->name, ($data->is_required == 1 ? 'required' : '')]) }}

                                                    {{-- Dropdown Field --}}
                                                @elseif($data->type == 'dropdown')
                                                    {{ Form::select(
                                                        'extra_fields['.$key.'][data]',$data->default_values,
                                                        null,
                                                        [
                                                            'id' => $fieldName,
                                                            'class' => 'form-control select-fields',
                                                            ($data->is_required == 1 ? 'required' : ''),
                                                            'placeholder' => 'Select '.$data->name
                                                        ]
                                                    )}}

                                                    {{-- Radio Field --}}
                                                @elseif($data->type == 'radio')
                                                    <label class="d-block">{{$data->name}} @if($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif</label>
                                                    <div class="row form-check-inline ml-1">
                                                        @foreach ($data->default_values as $keyRadio => $value)
                                                            <div class="col-md-12 col-lg-12 col-xl-6 col-sm-12 form-check">
                                                                <label class="form-check-label">
                                                                    {{ Form::radio('extra_fields['.$key.'][data]', $value, null, ['id' => $fieldName.'_'.$keyRadio, 'class' => 'radio-fields',($data->is_required == 1 ? 'required' : '')]) }}
                                                                    {{$value}}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    {{-- Checkbox Field --}}
                                                @elseif($data->type == 'checkbox')
                                                    <label class="d-block">{{$data->name}} @if($data->is_required)
                                                            <span class="text-danger">*</span>
                                                        @endif</label>
                                                    <div class="row form-check-inline ml-1">
                                                        @foreach ($data->default_values as $chkKey => $value)
                                                            <div class="col-lg-12 col-xl-6 col-md-12 col-sm-12 form-check">
                                                                <label class="form-check-label">
                                                                    {{ Form::checkbox('extra_fields['.$key.'][data][]', $value, null, ['id' => $fieldName.'_'.$chkKey, 'class' => 'form-check-input chkclass checkbox-fields',($data->is_required == 1 ? 'required' : '')]) }} {{ $value }}
                                                                </label>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    {{-- Textarea Field --}}
                                                @elseif($data->type == 'textarea')
                                                    {{ Form::textarea('extra_fields['.$key.'][data]', '', ['placeholder' => $data->name, 'id' => $fieldName, 'class' => 'form-control textarea-fields', ($data->is_required ? 'required' : '') , 'rows' => 3]) }}

                                                    {{-- File Upload Field --}}
                                                @elseif($data->type == 'file')
                                                    <div class="input-group col-xs-12">
                                                        {{ Form::file('extra_fields['.$key.'][data]', ['class' => 'file-upload-default', 'id' => $fieldName]) }}
                                                        {{ Form::text('', '', ['class' => 'form-control file-upload-info', 'disabled' => '', 'placeholder' => __('image')]) }}
                                                        <span class="input-group-append">
                                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                                        </span>
                                                    </div>
                                                    <div id="file_div_{{$fieldName}}" class="mt-2 d-none file-div">
                                                        <a href="" id="file_link_{{$fieldName}}" target="_blank">{{$data->name}}</a>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="d-flex">
                                        <div class="form-check w-fit-content">
                                            <label class="form-check-label ml-4">
                                                <input type="checkbox" class="form-check-input" name="reset_password" value="1">{{ __('reset_password') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            

                            <hr>
                            {{-- Guardian Details --}}
                            <div class="row mt-5">
                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('guardian') . ' ' . __('email') }} <span class="text-danger">*</span></label>
                                    <select class="edit-guardian-search form-control" name="guardian_id"></select>
                                    <input type="hidden" id="edit_guardian_email" name="guardian_email">
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('guardian') . ' ' . __('first_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('guardian_first_name', null, ['placeholder' => __('guardian') . ' ' . __('first_name'), 'class' => 'form-control', 'id' => 'edit_guardian_first_name']) !!}
                                </div>

                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('guardian') . ' ' . __('last_name') }} <span class="text-danger">*</span></label>
                                    {!! Form::text('guardian_last_name', null, ['placeholder' => __('guardian') . ' ' . __('last_name'), 'class' => 'form-control', 'id' => 'edit_guardian_last_name']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('guardian') . ' ' . __('mobile') }} <span class="text-danger">*</span></label>
                                    {!! Form::number('guardian_mobile', null, ['placeholder' => __('guardian') . ' ' . __('mobile'), 'class' => 'form-control remove-number-increment', 'min' => 1  ,'id' => 'edit_guardian_mobile']) !!}
                                </div>
                                <div class="form-group col-sm-12 col-md-12">
                                    <label>{{ __('gender') }} <span class="text-danger">*</span></label><br>
                                    <div class="d-flex">
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('guardian_gender', 'male', false , ['id' =>"edit-guardian-male"]) !!}
                                                {{-- <input type="radio" name="guardian_gender" value="male" id="edit_guardian_male"  disabled> --}}
                                                {{ __('male') }}
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <label class="form-check-label">
                                                {!! Form::radio('guardian_gender', 'female', false , ['id' =>"edit-guardian-female"]) !!}
                                                {{-- <input type="radio" name="guardian_gender" value="female" id="edit_guardian_female" disabled> --}}
                                                {{ __('female') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group col-sm-12 col-md-12 col-lg-6 col-xl-4">
                                    <label>{{ __('image') }} </label>
                                    <input type="file" name="guardian_image" class="file-upload-default"/>
                                    <div class="input-group col-xs-12">
                                        <input type="text" class="form-control file-upload-info" disabled="" placeholder="{{ __('image') }}" required="required" id="edit_image"/>
                                        <span class="input-group-append">
                                            <button class="file-upload-browse btn btn-theme" type="button">{{ __('upload') }}</button>
                                        </span>
                                    </div>
                                    <div style="width: 100px;">
                                        <img src="" id="edit-guardian-image-tag" class="img-fluid w-100" alt=""/>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group col-sm-12 col-md-4">
                                    <div class="d-flex">
                                        <div class="form-check w-fit-content">
                                            <label class="form-check-label ml-4">
                                                <input type="checkbox" class="form-check-input" name="parent_reset_password" value="1">{{ __('reset_password') }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Cancel') }}</button>
                            <input class="btn btn-theme" type="submit" value={{ __('submit') }}>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan

    <!-- ID Card Preview Modal -->
    <div class="modal fade" id="idCardPreviewModal" tabindex="-1" role="dialog" aria-labelledby="idCardPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="idCardPreviewModalLabel">{{ __('Student ID Card Preview') }}</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <div class="student-id-card-modal" id="modalIdCard">
                        <div class="id-card-header-modal">
                            <div class="school-logo-modal">
                                <i class="fas fa-school"></i>
                            </div>
                            <div class="school-name-modal">{{ Auth::user()->school->name ?? 'School Name' }}</div>
                        </div>
                        <div class="id-card-body-modal">
                            <div class="student-photo-modal">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <div class="student-info-modal">
                                <div class="info-row-modal">
                                    <span class="label-modal">Name:</span>
                                    <span class="value-modal" id="modalIdCardName">Student Name</span>
                                </div>
                                <div class="info-row-modal">
                                    <span class="label-modal">Class:</span>
                                    <span class="value-modal" id="modalIdCardClass">-</span>
                                </div>
                                <div class="info-row-modal">
                                    <span class="label-modal">GR No:</span>
                                    <span class="value-modal" id="modalIdCardGr">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="id-card-footer-modal">
                            <div class="barcode-section-modal">
                                <div class="barcode id-barcode-modal" id="modalIdCardBarcode"></div>
                                <div class="barcode-number-modal" id="modalIdCardBarcodeNumber">-</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="button" class="btn btn-primary" onclick="printModalIdCard()">
                        <i class="fas fa-print"></i> {{ __('Print ID Card') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* ID Card Modal Styles */
        .student-id-card-modal {
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
        .id-card-header-modal {
            background: #fff;
            padding: 12px;
            text-align: center;
            border-bottom: 2px solid #333;
        }
        .school-logo-modal {
            font-size: 24px;
            color: #667eea;
            margin-bottom: 3px;
        }
        .school-name-modal {
            font-size: 11px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            line-height: 1.2;
        }
        .id-card-body-modal {
            flex: 1;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #fff;
        }
        .student-photo-modal {
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
        .student-photo-modal i {
            font-size: 45px;
            color: #667eea;
        }
        .student-info-modal {
            width: 100%;
        }
        .info-row-modal {
            display: flex;
            margin-bottom: 6px;
            font-size: 12px;
        }
        .info-row-modal .label-modal {
            font-weight: bold;
            min-width: 50px;
            color: #fff;
        }
        .info-row-modal .value-modal {
            color: #fff;
            flex: 1;
        }
        .id-card-footer-modal {
            background: #fff;
            padding: 10px 8px 8px 8px;
            text-align: center;
        }
        .barcode-section-modal {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .id-barcode-modal {
            height: 45px;
            width: 100%;
            margin-bottom: 4px;
        }
        .barcode-number-modal {
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
        let userIds;
        $('.table-list-type').on('click', function (e) {
            let value = $(this).data('id');
            let ActiveLang = window.trans['Active'];
            let DeactiveLang = window.trans['Inactive'];
            if (value === "" || value === 0 || value == null) {
                $("#update-status").data("id")
                $('.update-status-btn-name').html(DeactiveLang);
            } else {
                $('.update-status-btn-name').html(ActiveLang);
            }
        })


        function updateUserStatus(tableId, buttonClass) {
            let selectedRows = $(tableId).bootstrapTable('getSelections');
            let selectedRowsValues = selectedRows.map(function (row) {
                return row.user_id;
            });
            userIds = JSON.stringify(selectedRowsValues);

            if (buttonClass != null) {
                if (selectedRowsValues.length) {
                    $(buttonClass).prop('disabled', false);
                } else {
                    $(buttonClass).prop('disabled', true);
                }
            }
        }

        $('#table_list').bootstrapTable({
            onCheck: function (row) {
                updateUserStatus("#table_list", '#update-status');
            },
            onUncheck: function (row) {
                updateUserStatus("#table_list", '#update-status');
            },
            onCheckAll: function (rows) {
                updateUserStatus("#table_list", '#update-status');
            },
            onUncheckAll: function (rows) {
                updateUserStatus("#table_list", '#update-status');
            }
        });
        $("#update-status").on('click', function (e) {
            Swal.fire({
                title: window.trans["Are you sure"],
                text: window.trans["Change Status For Selected Users"],
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: window.trans["Yes, Change it"],
                cancelButtonText: window.trans["Cancel"]
            }).then((result) => {
                if (result.isConfirmed) {
                    let url = baseUrl + '/students/change-status-bulk';
                    let data = new FormData();
                    data.append("ids", userIds)

                    function successCallback(response) {
                        $('#table_list').bootstrapTable('refresh');
                        $('#update-status').prop('disabled', true);
                        userIds = null;
                        showSuccessToast(response.message);
                    }

                    function errorCallback(response) {
                        showErrorToast(response.message);
                    }

                    ajaxRequest('POST', url, data, null, successCallback, errorCallback);
                }
            })
        })

        // ID Card Preview Functions - Generate Code 128 Barcode using JsBarcode
        function generateBarcodeForModal(text) {
            if (!text) return;
            
            // Clear previous barcode
            $('#modalIdCardBarcode').empty();
            
            // Create a canvas element
            var canvas = document.createElement('canvas');
            $('#modalIdCardBarcode').append(canvas);
            
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

        // Student Events Handler for ID Card Preview
        window.studentEvents = {
            'click .preview-id-card': function (e, value, row, index) {
                e.preventDefault();
                var studentName = $(e.currentTarget).data('student-name');
                var grNumber = $(e.currentTarget).data('gr-number');
                var className = $(e.currentTarget).data('class');
                
                $('#modalIdCardName').text(studentName);
                $('#modalIdCardClass').text(className);
                $('#modalIdCardGr').text(grNumber);
                $('#modalIdCardBarcodeNumber').text(grNumber);
                
                generateBarcodeForModal(grNumber);
                $('#idCardPreviewModal').modal('show');
            }
        };

        // Print ID Card from Modal
        function printModalIdCard() {
            // Get the canvas barcode data
            var originalBarcode = document.getElementById('modalIdCardBarcode');
            var canvas = originalBarcode.querySelector('canvas');
            var barcodeDataUrl = canvas ? canvas.toDataURL('image/png') : '';
            
            // Clone the modal card
            var originalCard = document.getElementById('modalIdCard');
            var clonedCard = originalCard.cloneNode(true);
            
            // Replace the barcode div with an image
            var clonedBarcode = clonedCard.querySelector('#modalIdCardBarcode');
            if (clonedBarcode && barcodeDataUrl) {
                clonedBarcode.innerHTML = '<img src="' + barcodeDataUrl + '" style="width:100%;height:25px;"/>';
            }
            
            var printContent = clonedCard.outerHTML;
            var printWindow = window.open('', '_blank', 'width=400,height=600');
            printWindow.document.write('<html><head><title>Student ID Card</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body{margin:20px;display:flex;justify-content:center;align-items:center;font-family:Arial,sans-serif}');
            printWindow.document.write('.student-id-card-modal{width:250px;height:380px;border:2px solid #000;border-radius:10px;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 4px 8px rgba(0,0,0,0.2)}');
            printWindow.document.write('.id-card-header-modal{background:#fff;padding:12px;text-align:center;border-bottom:2px solid #000}');
            printWindow.document.write('.school-logo-modal{font-size:24px;color:#667eea;margin-bottom:3px}');
            printWindow.document.write('.school-name-modal{font-size:11px;font-weight:bold;color:#000;text-transform:uppercase}');
            printWindow.document.write('.id-card-body-modal{flex:1;padding:15px;display:flex;flex-direction:column;align-items:center;color:#fff}');
            printWindow.document.write('.student-photo-modal{width:70px;height:70px;border-radius:50%;background:#fff;display:flex;align-items:center;justify-content:center;margin-bottom:12px;border:3px solid #fff;font-size:45px;color:#667eea}');
            printWindow.document.write('.student-info-modal{width:100%}');
            printWindow.document.write('.info-row-modal{display:flex;margin-bottom:8px;font-size:13px}');
            printWindow.document.write('.info-row-modal .label-modal{font-weight:bold;min-width:50px;color:#fff}');
            printWindow.document.write('.info-row-modal .value-modal{color:#fff;flex:1}');
            printWindow.document.write('.id-card-footer-modal{background:#fff;padding:10px 8px 8px 8px;text-align:center}');
            printWindow.document.write('.id-barcode-modal{height:25px;width:100%;margin-bottom:4px}');
            printWindow.document.write('.id-barcode-modal svg{width:100%;height:25px}');
            printWindow.document.write('.id-barcode-modal rect{fill:#000}');
            printWindow.document.write('.barcode-number-modal{font-family:monospace;font-size:9px;color:#000;font-weight:bold;letter-spacing:1px}');
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
    </script>
@endsection
