<!DOCTYPE html>
<html lang="en">
    @php
    $lang = Session::get('language');
@endphp
@if($lang)
    @if ($lang->is_rtl)
        <html lang="en" dir="rtl">
    @else
        <html lang="en" dir="ltl">
    @endif
@else
    <html lang="en" dir="ltl">
@endif
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="{{ asset('assets/home_page/css/style.css') }}" rel="stylesheet">

    <title>{{ __('login') }} || {{ config('app.name') }}</title>

    @include('layouts.include')

    <style>
        :root {
        --primary-color: {{ $systemSettings['theme_primary_color'] ?? '#56cc99' }};
        --secondary-color: {{ $systemSettings['theme_secondary_color'] ?? '#215679' }};
        --secondary-color1: {{ $systemSettings['theme_secondary_color_1'] ?? '#38a3a5' }};
        --primary-background-color: {{ $systemSettings['theme_primary_background_color'] ?? '#f2f5f7' }};
        --text--secondary-color: {{ $systemSettings['theme_text_secondary_color'] ?? '#5c788c' }};
        
    }
    .modal .modal-dialog {
        margin-top: unset !important;
    }
    a {
        color: #007bff !important;
    }

    /* Modern Login Styles */
    body {
        background: linear-gradient(135deg, #1a365d 0%, #2c5282 50%, #38a3a5 100%) !important;
        min-height: 100vh;
    }
    .auth-form-light {
        background: rgba(255, 255, 255, 0.95) !important;
        backdrop-filter: blur(10px);
        border-radius: 20px !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 0 0 1px rgba(255, 255, 255, 0.1);
        border: none !important;
        padding: 2.5rem !important;
    }
    .brand-logo img {
        width: auto !important;
        max-width: 180px;
        height: 60px;
        object-fit: contain;
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-group label {
        font-weight: 600;
        color: #1a365d;
        font-size: 0.875rem;
        margin-bottom: 0.5rem;
        display: block;
    }
    .form-control {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-radius: 12px !important;
        padding: 1rem 1.25rem;
        font-size: 0.9375rem;
        transition: all 0.3s ease;
    }
    .form-control:focus {
        background: #fff;
        border-color: #38a3a5;
        box-shadow: 0 0 0 4px rgba(56, 163, 165, 0.1);
        outline: none;
    }
    .input-group-text {
        background: #f8fafc;
        border: 2px solid #e2e8f0;
        border-left: none;
        border-radius: 0 12px 12px 0 !important;
        color: #64748b;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .input-group-text:hover {
        background: #e2e8f0;
        color: #1a365d;
    }
    .btn-theme {
        background: linear-gradient(135deg, #d69e2e 0%, #b7791f 100%);
        border: none;
        border-radius: 12px;
        color: white;
        font-weight: 600;
        font-size: 1rem;
        padding: 1rem;
        letter-spacing: 0.025em;
        box-shadow: 0 10px 15px -3px rgba(214, 158, 46, 0.4);
        transition: all 0.3s ease;
    }
    .btn-theme:hover {
        transform: translateY(-2px);
        box-shadow: 0 20px 25px -5px rgba(214, 158, 46, 0.5);
        background: linear-gradient(135deg, #ecc94b 0%, #d69e2e 100%);
    }
    .auth-link {
        color: #38a3a5 !important;
        font-weight: 500;
        font-size: 0.875rem;
        text-decoration: none;
        transition: color 0.3s ease;
    }
    .auth-link:hover {
        color: #2c7a7b !important;
    }
    .signup-link {
        background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
        color: white !important;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-block;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .signup-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(26, 54, 93, 0.3);
    }
    .alert {
        border-radius: 12px;
        border: none;
    }

    /* Modal Fix - Ensure inputs are focusable */
    .modal {
        z-index: 9999 !important;
        display: none;
    }
    .modal.show {
        display: block !important;
    }
    .modal-backdrop {
        z-index: 9998 !important;
        opacity: 0.5 !important;
    }
    .modal-content {
        z-index: 10000 !important;
        background: white !important;
        border-radius: 16px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }
    .modal-dialog {
        pointer-events: auto !important;
        max-width: 900px;
    }
    .modal-body input,
    .modal-body select,
    .modal-body textarea {
        pointer-events: auto !important;
        z-index: 10001 !important;
    }
    .formModal .modal-header {
        background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%);
        color: white;
        border-radius: 16px 16px 0 0;
    }
    .formModal .modal-title {
        color: white;
        font-weight: 600;
    }
    .formModal .btn-close {
        filter: invert(1);
    }

    /* Modal Form Styling */
    .formModal .modal-body {
        padding: 2rem;
    }
    .formModal .schoolFormWrapper {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1rem;
    }
    .formModal .headingWrapper {
        text-align: center;
        margin-bottom: 1.5rem;
    }
    .formModal .headingWrapper span {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1a365d;
    }
    .formModal .inputWrapper {
        margin-bottom: 1rem;
    }
    .formModal .inputWrapper label {
        display: block;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }
    .formModal input[type="text"],
    .formModal input[type="email"],
    .formModal input[type="password"],
    .formModal select,
    .formModal textarea {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: all 0.3s ease;
        background: white;
    }
    .formModal input:focus,
    .formModal select:focus,
    .formModal textarea:focus {
        border-color: #38a3a5;
        box-shadow: 0 0 0 3px rgba(56, 163, 165, 0.1);
        outline: none;
    }
    .formModal .commonBtn {
        background: linear-gradient(135deg, #d69e2e 0%, #b7791f 100%);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .formModal .commonBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px -5px rgba(214, 158, 46, 0.4);
    }
    .formModal .modalfooter {
        display: flex;
        justify-content: flex-end;
        margin-top: 1.5rem;
    }
    </style>
    <script async src="https://www.google.com/recaptcha/api.js"></script>
</head>

<body>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper login-d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-xl-5 mx-auto">
                        <div class="auth-form-light">
                        @if (env('DEMO_MODE'))
                        <div class="alert alert-info text-center" role="alert">
                            NOTE : <a target="_blank" href="https://elimugrid.com/login">-- Click Here --</a> if you cannot login.
                        </div>
                        @endif
                        <div class="text-left">
                            <div class="brand-logo text-center">
                                @if ($schoolSettings['horizontal_logo'] ?? '')
                                    <img class="img-fluid w-25" src="{{ $schoolSettings['horizontal_logo'] ?? '' }}" alt="logo">    
                                @elseif($systemSettings['login_page_logo'] ?? $systemSettings['horizontal_logo'] ?? '')
                                    <img class="img-fluid w-25" src="{{ $systemSettings['login_page_logo'] ?? $systemSettings['horizontal_logo'] ?? '' }}" alt="logo">
                                @else
                                    <img class="img-fluid w-25" src="{{ url('images/elimugrid/elogo.png') }}" alt="ElimuGrid">
                                @endif

                            </div>
                            <div class="mt-3">
                                {{-- emailSuccess --}}
                                @if (\Session::has('emailSuccess'))
                                    <div class="alert alert-success text-center" role="alert">
                                        {{ \Session::get('emailSuccess') }}.
                                    </div>
                                @endif
                                @if (\Session::has('success'))
                                    <div class="alert alert-success text-center" role="alert">
                                        {{ \Session::get('success') }}.
                                    </div>
                                    <div class="alert alert-success text-center mt-2" role="alert">
                                        Please ensure you use your registered email for login, and your contact number as the password.
                                    </div>
                                @endif
                                {{-- emailError --}}
                                @if (\Session::has('emailError'))
                                    <div class="alert alert-danger text-center" role="alert">
                                        {{ \Session::get('emailError') }}.
                                    </div>
                                @endif
                                @if (\Session::has('error'))
                                    <div class="alert alert-danger text-center" role="alert">
                                        {{ \Session::get('error') }}.
                                    </div>
                                @endif
                            </div>
                            <form action="{{ route('login') }}" id="frmLogin" method="POST" class="pt-3">
                                @csrf
                                <div class="form-group">
                                    <label for="email">{{ __('email') }}</label>
                                    <input id="email" type="text" class="form-control rounded-lg form-control-lg"
                                        name="email" value="{{ isset($school) && $school->type == 'demo' ? $school->user->email :old('email') }}" required autocomplete="email"
                                        autofocus placeholder="{{ __('email_or_mobile') }}">
                                </div>
                                <div class="form-group">
                                    <label for="password">{{ __('password') }}</label>
                                    <div class="input-group">
                                        <input id="password" type="password"
                                            class="form-control rounded-lg form-control-lg" name="password" required value="{{ isset($school) && $school->type == 'demo' ? $school->user->mobile : '' }}"
                                            autocomplete="current-password" placeholder="{{ __('password') }}">
                                        <div class="input-group-append" cursor="pointer" id="togglePasswordShowHide">
                                            <span class="input-group-text">
                                                <i class="fa fa-eye-slash" id="togglePassword"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                @if ($school ?? '')
                                    <div class="form-group d-none">
                                        <label for="school_code">{{ __('school_code') }}</label>
                                        <input id="school_code" type="text" class="form-control rounded-lg form-control-lg"
                                            name="code" value="{{ $school->code }}" autocomplete="school_code"
                                            autofocus placeholder="{{ __('school_code') }}">
                                    </div>
                                @else
                                    <div class="form-group">
                                        <label for="school_code">{{ __('school_code') }}</label>
                                        <input id="school_code" type="text" class="form-control rounded-lg form-control-lg"
                                            name="code" value="{{ old('code') }}" autocomplete="school_code"
                                            autofocus placeholder="{{ __('school_code') }}">
                                    </div>
                                @endif
                                

                                @if (Route::has('password.request'))
                                    <div class="my-2 d-flex justify-content-end align-items-center">
                                        <a class="auth-link text-blue" href="{{ route('password.request') }}">
                                            {{ __('forgot_password') }}
                                        </a>
                                    </div>
                                @endif
                                <div class="mt-4">
                                    <input type="submit" name="btnlogin" id="login_btn" value="{{ __('login') }}"
                                        class="btn btn-block btn-theme btn-lg font-weight-medium w-100" />
                                </div>
                                <div class="mt-4 text-center">
                                    <a class="signup-link" href="#" data-bs-toggle="modal" data-bs-target="#staticBackdrop">
                                        <i class="fas fa-user-plus me-2"></i>{{ __('New user? Sign up now') }}
                                    </a>
                                </div>
                            </form>
                            @if (env('DEMO_MODE'))

                            <div class="row mt-3">
                                <hr style="width: -webkit-fill-available;">
                                <div class="col-12 text-center mb-4 text-black-50">Demo Credentials</div>
                            </div>
                            @if (empty($school) ?? '')
                                <div class="col-12 text-center">
                                    Super Admin Panels
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <button class="btn w-100 btn-success mt-2" id="superadmin_btn">Super Admin</button>
                                    </div>

                                    <div class="col-md-6">
                                        <button class="btn w-100 btn-info mt-2" id="superadmin_staff_btn">Staff</button>
                                    </div>
                                </div>
                            @endif
                                
                                <div class="col-12 text-center mt-3">
                                    <hr class="w-100">
                                    School Admin Panels
                                </div>

                                <div class="row mt-3">
                                    <div class="col-md-4">
                                        <button class="btn w-100 btn-info mt-2" id="schooladmin_btn">School Admin</button>
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn w-100 btn-danger mt-2" id="teacher_btn">Teacher</button>
                                    </div>

                                    <div class="col-md-4">
                                        <button class="btn w-100 btn-primary mt-2" id="schooladmin_staff_btn">Staff</button>
                                    </div>
                                </div>

                            @endif
                        </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- content-wrapper ends -->
        </div>
        <!-- page-body-wrapper ends -->
    </div>

    <script src="{{ asset('/assets/js/vendor.bundle.base.js') }}"></script>
    <script src="{{ asset('/assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('/assets/jquery-toast-plugin/jquery.toast.min.js') }}"></script>
    <script src="{{ asset('/assets/js/custom/common.js') }}"></script>
    <script src="{{ asset('/assets/js/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('/assets/js/custom/function.js') }}"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>

    <script type='text/javascript'>
        $("#frmLogin").validate({
            rules: {
                username: "required",
                password: "required",
            },
            success: function(label, element) {
                $(element).parent().removeClass('has-danger')
                $(element).removeClass('form-control-danger')
            },
            errorPlacement: function(label, element) {
                if (label.text()) {
                    if ($(element).attr("name") == "password") {
                        label.insertAfter(element.parent()).addClass('text-danger mt-2');
                    } else {
                        label.addClass('mt-2 text-danger');
                        label.insertAfter(element);
                    }
                }
            },
            highlight: function(element, errorClass) {
                $(element).parent().addClass('has-danger')
                $(element).addClass('form-control-danger')
            }
        });

        const togglePassword = document.querySelector("#togglePasswordShowHide");
        const password = document.querySelector("#password");

        togglePassword.addEventListener("click", function() {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            // this.classList.toggle("fa-eye");
            if (password.getAttribute("type") === 'password') {
                $('#togglePassword').addClass('fa-eye-slash');
                $('#togglePassword').removeClass('fa-eye');
            } else {
                $('#togglePassword').removeClass('fa-eye-slash');
                $('#togglePassword').addClass('fa-eye');
            }
        });

        @if (env('DEMO_MODE'))
        // Super admin panel
            $('#superadmin_btn').on('click', function(e) {
                $('#email').val('superadmin@gmail.com');
                $('#password').val('superadmin');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })

            $('#superadmin_staff_btn').on('click', function(e) {
                $('#email').val('mahesh@gmail.com');
                $('#password').val('staff@123');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })

            // School Panel
        $('#schooladmin_btn').on('click', function (e) {
            $('#email').val('school1@gmail.com');
            $('#password').val('school@123');
            $('#school_code').val('SCH202412');
            $('#login_btn').attr('disabled', true);
            $(this).attr('disabled', true);
            $('#frmLogin').submit();
        })
            $('#teacher_btn').on('click', function(e) {
                $('#email').val('teacher@gmail.com');
                $('#password').val('0111111111');
                $('#school_code').val('SCH202412');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })

            $('#schooladmin_staff_btn').on('click', function(e) {
                $('#email').val('smitc@gmail.com');
                $('#password').val('965555885');
                $('#school_code').val('SCH202412');
                $('#login_btn').attr('disabled', true);
                $(this).attr('disabled', true);
                $('#frmLogin').submit();
            })
        @endif

        const please_wait = "{{__('Please wait')}}"
        const processing_your_request = "{{__('Processing your request')}}"
    </script>

    @include('registration_form')
</body>

@if (Session::has('error'))
    <script type='text/javascript'>
        $.toast({
            text: '{{ Session::get('error') }}',
            showHideTransition: 'slide',
            icon: 'error',
            loaderBg: '#f2a654',
            position: 'top-right'
        });
    </script>
@endif

@if ($errors->any())
    @foreach ($errors->all() as $error)
        <script type='text/javascript'>
            $.toast({
                text: '{{ $error }}',
                showHideTransition: 'slide',
                icon: 'error',
                loaderBg: '#f2a654',
                position: 'top-right'
            });
        </script>
    @endforeach
@endif

</html>
