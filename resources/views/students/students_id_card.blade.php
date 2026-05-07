<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    @php
    // Code 128 Barcode Generator Function
    function generateCode128Barcode($text) {
        if (empty($text)) return '';
        
        // Code 128 C patterns (values 00-99)
        $patterns = [
            '11011001100','11001101100','11001100110','10010011000','10010001100',
            '10001001100','10011001000','10011000100','10001100100','11001001000',
            '11001000100','11000100100','10110011100','10011011100','10011001110',
            '10111001100','10011101100','10011100110','11001110010','11001011100',
            '11001001110','11011100100','11001110100','11101101110','11101001100',
            '11100101100','11100100110','11101100100','11100110100','11100110010',
            '11011011100','11011001110','11001110110','11101101110','11101011100',
            '11101001110','11100101110','11100111010','11100110110','11011000100',
            '11000110100','10110010000','10110001000','10011010000','10011001000',
            '10010110000','10010011000','10100011000','10001011000','10011000100',
            '10001100100','10110011100','10011011100','10011001110','10111001100',
            '10011101100','10011100110','11001110010','11001011100','11001001110',
            '11011100100','11001110100','11101101110','11101001100','11100101100',
            '11100100110','11101100100','11100110100','11100110010','11011011100',
            '11011001110','11001110110','11011101110','11011110110','11110110110'
        ];
        $startC = '11010011100';
        $stop = '1100011101011';
        
        // Build barcode binary string
        $binary = $startC;
        for ($i = 0; $i < strlen($text); $i += 2) {
            $pair = substr($text, $i, 2);
            if (strlen($pair) == 2 && is_numeric($pair)) {
                $val = intval($pair);
                $binary .= $patterns[$val] ?? $patterns[0];
            } else {
                $val = intval($text[$i] ?? 0);
                $binary .= $patterns[$val] ?? $patterns[0];
            }
        }
        $binary .= $stop;
        
        // Calculate image dimensions
        $barWidth = 1.5;
        $height = 25;
        $width = strlen($binary) * $barWidth + 20;
        
        // Create image
        $img = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);
        
        // Draw bars
        $x = 10;
        for ($i = 0; $i < strlen($binary); $i++) {
            if ($binary[$i] === '1') {
                imagefilledrectangle($img, $x, 3, $x + $barWidth - 1, $height - 3, $black);
            }
            $x += $barWidth;
        }
        
        // Output to base64
        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);
        
        return 'data:image/png;base64,' . base64_encode($data);
    }
    @endphp

    <style>
        * {
            font-family: 'DejaVu Sans', sans-serif;
        }
        html, body {
            margin: 0px !important;
        }
        .full-width
        {
            width: 100%;
        }

        .header th{
            padding: 10px 0px;
            background-color: {{ $settings['header_color'] ?? '#00edff' }};
            color: {{ $settings['header_footer_text_color'] ?? 'black' }};
        }
        table {
            border-collapse: collapse;
            border: none;
            font-size: 14px;
            z-index: 1;
        }
        .student-image {
            width: 30%;
            padding: 0px 10px;
            text-align: center;
            vertical-align: middle;
            height: 80px;
        }
        .student-data {
            text-align: left;
            padding-left: 10px;
            padding: 2px 5px;
        }
        .card-title {
            padding: 6px 0px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .school-name {
            padding-right: 10px !important;
            text-align: right;
            font-size: 15px;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom-right-radius: 10px;
        }
        
        .footer {
            background-color: {{ $settings['footer_color'] ?? '#56cc99' }};
            color: {{ $settings['header_footer_text_color'] ?? 'black' }};
            position: fixed;
            width: 100%;
            padding: 2px 0px;
            font-size: 12px;
            bottom: 0px;
            height: 15px;
            text-align: right;
            letter-spacing: 1.5px;
            z-index: 1;
            padding-bottom: 5px;
        }
        .school-logo {
            border-bottom-left-radius: 10px;
        }
        .card-body {
            height: {{ $settings['page_height'] ?? '100%' }};
        }
        .vertical-student-data {
            text-align: left;
            padding: 2px 2px 5px 10px;
        }
        .signature {
            background-size: contain;
            background-position: center center;
            background-repeat: no-repeat;
            padding: 10px;
            position: fixed;
            bottom: 35px;
            right: 10px;   
        }
        .vertical-school-name {
            padding: 10px 10px !important;
            text-align: center;
            font-size: 15px;
            text-transform: uppercase;
            font-weight: bold;
            border-bottom-right-radius: 10px;
            border-bottom-left-radius: 10px;
        }
        
        /* Barcode Styles */
        .barcode-row {
            border: none !important;
        }
        .barcode-row td {
            border: none !important;
            padding: 0 !important;
        }
        .barcode-container {
            text-align: center;
            padding: 8px 5px 5px 5px;
            background: transparent;
            margin-top: 5px;
        }
        .barcode-lines {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            height: 40px;
            margin-bottom: 2px;
        }
        .barcode-line {
            display: inline-block;
            background: #000;
            margin: 0;
        }
        .barcode-number {
            font-family: 'DejaVu Sans', monospace;
            font-size: 9px;
            font-weight: bold;
            color: #000;
            letter-spacing: 1px;
        }
    </style>

    @if (isset($settings['profile_image_style']) && $settings['profile_image_style'] == 'squre')
        <style>
            .student-profile {
                border: 3px solid black;
                border-radius: 6px;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
                padding: 2px;
        }
        </style>
    @else
        <style>
            .student-profile {
                border: 3px solid black;
                border-radius: 80px;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
                padding: 2px;
        }
        </style>
    @endif

    @if (isset($settings['layout_type']) && $settings['layout_type'] == 'horizontal')
        <style>
            .background-image {
                position: fixed;
                width: auto;
                padding: 5px;
                height: auto;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.2;
                z-index: -1;
            }
            .background_image {
                z-index: -1;
                object-fit: cover;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
            }

        </style>
    @else
        <style>
            .background-image {
                position: fixed;
                width: auto;
                padding: 5px;
                height: auto;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                opacity: 0.2;
                z-index: -1;
            }
            .background_image {
                z-index: -1;
                object-fit: cover;
                background-size: contain;
                background-position: center center;
                background-repeat: no-repeat;
            }
        </style>
    @endif
</head>
<body>
    @foreach ($students as $key => $student)
    <div class="card-body">
        @if ($settings['layout_type'] == 'horizontal')
            
            <table class="table full-width">
                <tr class="header">
                    <th class="school-logo">
                        @if ($settings['horizontal_logo'] ?? '')
                            <img height="40" src="{{ public_path('storage/').$settings['horizontal_logo'] }}" alt="">
                        @else
                            <img height="40" src="{{ public_path('assets/horizontal-logo2.svg') }}" alt="">
                        @endif
                    </th>
                    <th class="school-name" colspan="2">{{ $settings['school_name'] }}</th>
                </tr>
                <tr>
                    <th class="card-title" colspan="3">Student Identification Card</th>
                </tr>
                <tr>
                    <td class="student-image" rowspan="{{ count($settings['student_id_card_fields']) + count($student->extra_student_details) }}">
                        @if ($student->getRawOriginal('image'))
                            <img class="student-profile" height="120" width="120" align="center" src="{{ public_path('storage/').$student->getRawOriginal('image') }}" alt="">
                        @else
                            <img class="student-profile" height="120" width="120" align="center" src="{{ public_path('assets/dummy_logo.jpg') }}" alt="">    
                        @endif
                        
                    </td>
                    @if (in_array('student_name',$settings['student_id_card_fields']))
                        <th class="student-data">Student Name :</th>
                        <td>{{ $student->full_name }}</td>
                    @endif
                    
                </tr>
                @if (in_array('class_section',$settings['student_id_card_fields']))
                <tr>
                    <th class="student-data">Class Section :</th>
                    <td>{{ $student->student->class_section->full_name }}</td>
                </tr>
                @endif

                @if (in_array('roll_no',$settings['student_id_card_fields']))
                <tr>
                    <th class="student-data">Roll No. :</th>
                    <td>{{ $student->student->roll_number }}</td>
                </tr>
                @endif

                @if (in_array('dob',$settings['student_id_card_fields']))
                <tr>
                    <th class="student-data">DOB :</th>
                    <td>{{ date($settings['date_format'],strtotime($student->dob)) }}</td>
                </tr>
                @endif

                @if (in_array('gender',$settings['student_id_card_fields']))
                <tr>
                    <th class="student-data">Gender :</th>
                    <td style="text-transform: capitalize">{{ $student->gender }}</td>
                </tr>
                @endif

                @if (in_array('session_year',$settings['student_id_card_fields']))
                <tr>
                    <th class="student-data">Session Year :</th>
                    <td>{{ $sessionYear->name }}</td>
                </tr>
                @endif

                @if (in_array('guardian_name',$settings['student_id_card_fields']))
                <tr>
                    <th class="student-data">Guardian Name :</th>
                    <td>{{ $student->student->guardian->full_name }}</td>
                </tr>
                @endif

                @if (in_array('guardian_contact',$settings['student_id_card_fields']))
                <tr>
                    <th class="student-data">Guardian Contact :</th>
                    <td>{{ $student->student->guardian->mobile }}</td>
                </tr>
                @endif

                <?php $processedFieldsHorizontal = []; ?>
                @foreach ($student->extra_student_details as $data)
                    @php
                        // Skip this iteration if we've already processed a field with this name
                        $fieldName = $data->form_field->name;
                        if (isset($processedFieldsHorizontal[$fieldName])) continue;
                        $processedFieldsHorizontal[$fieldName] = true;
                    @endphp
                    
                    @if (in_array($data->form_field->type, ['text','number','radio','textarea']))
                        <tr>
                            <th class="vertical-student-data">{{ $data->form_field->name }} :</th>
                            <td>{{ $data->data }}</td>
                        </tr>
                    @elseif($data->form_field->type == 'dropdown')
                        <tr>
                            <th class="vertical-student-data">{{ $data->form_field->name }} :</th>
                            <td>{!! isset($data->form_field->default_values[$data->data]) ? $data->form_field->default_values[$data->data] : $data->data !!}</td>
                        </tr>
                    @elseif($data->form_field->type == 'checkbox')
                        <tr>
                            <th class="vertical-student-data">{{ $data->form_field->name }} :</th>
                            <td>{!! implode(",",json_decode($data->data ?? '[]')) !!}</td>
                        </tr>
                    @endif
                @endforeach

                <tr>
                    <td></td>
                    <td colspan="">
                        @if ($settings['signature'] ?? '')
                            <img class="" height="40" class="signature" width="100" align="center" src="{{ public_path('storage/').$settings['signature'] }}" alt="">
                            <span style="position: fixed;bottom:25px;right:40px"><b>Signature</b></span>
                        @endif
                    </td>
                </tr>
                
                <!-- Barcode Section - Horizontal Layout -->
                <tr class="barcode-row">
                    <td colspan="3">
                        <div class="barcode-container">
                            @php $grNumber = $student->student->admission_no ?? ''; @endphp
                            <img src="{{ generateCode128Barcode($grNumber) }}" alt="{{ $grNumber }}" style="width:100%;height:25px;"/>
                            <div class="barcode-number">{{ $grNumber }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        @else
            {{-- Vertical --}}
            <table class="table full-width">
                <tr class="header">
                    <th class="vertical-school-name" colspan="2">{{ $settings['school_name'] }}</th>
                </tr>
                <tr>
                    <th colspan="2">
                        @if ($settings['horizontal_logo'] ?? '')
                            <img height="40" style="padding-top: 5px" src="{{ public_path('storage/').$settings['horizontal_logo'] }}" alt="">
                        @else
                            <img height="40" style="padding-top: 5px" src="{{ public_path('assets/horizontal-logo2.svg') }}" alt="">
                        @endif
                    </th>
                </tr>
                <tr>
                    <th class="card-title" colspan="2" style="font-size: 12px">Student Identification Card</th>
                </tr>
                <tr>
                    <td class="student-image" colspan="2">
                        @if ($student->getRawOriginal('image'))
                            <img class="student-profile" height="120" width="120" align="center" src="{{ public_path('storage/').$student->getRawOriginal('image') }}" alt="">
                        @else
                            <img class="student-profile" height="120" width="120" align="center" src="{{ public_path('assets/dummy_logo.jpg') }}" alt="">    
                        @endif
                    </td>
                </tr>

                @if (in_array('student_name',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">Name :</th>
                    <td>{{ $student->full_name }}</td>
                </tr>
                @endif

                @if (in_array('class_section',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">Class Section :</th>
                    <td>{{ $student->student->class_section->full_name }}</td>
                </tr>
                @endif

                @if (in_array('roll_no',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">Roll No. :</th>
                    <td>{{ $student->student->roll_number }}</td>
                </tr>
                @endif

                @if (in_array('dob',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">DOB :</th>
                    <td>{{ date($settings['date_format'],strtotime($student->dob)) }}</td>
                </tr>
                @endif

                @if (in_array('gender',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">Gender :</th>
                    <td style="text-transform: capitalize">{{ $student->gender }}</td>
                </tr>
                @endif

                @if (in_array('session_year',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">Session Year :</th>
                    <td>{{ $sessionYear->name }}</td>
                </tr>
                @endif

                @if (in_array('guardian_name',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">Guardian Name :</th>
                    <td>{{ $student->student->guardian->full_name }}</td>
                </tr>
                @endif

                @if (in_array('guardian_contact',$settings['student_id_card_fields']))
                <tr>
                    <th class="vertical-student-data">Guardian Contact :</th>
                    <td>{{ $student->student->guardian->mobile }}</td>
                </tr>
                @endif

                <?php $processedFieldsVertical = []; ?>
                @foreach ($student->extra_student_details as $data)
                    @php
                        // Skip this iteration if we've already processed a field with this name
                        $fieldName = $data->form_field->name;
                        if (isset($processedFieldsVertical[$fieldName])) continue;
                        $processedFieldsVertical[$fieldName] = true;
                    @endphp
                    
                    @if (in_array($data->form_field->type, ['text','number','radio','textarea']))
                        <tr>
                            <th class="vertical-student-data">{{ $data->form_field->name }} :</th>
                            <td>{{ $data->data }}</td>
                        </tr>
                    @elseif($data->form_field->type == 'dropdown')
                        <tr>
                            <th class="vertical-student-data">{{ $data->form_field->name }} :</th>
                            <td>{!! isset($data->form_field->default_values[$data->data]) ? $data->form_field->default_values[$data->data] : $data->data !!}</td>
                        </tr>
                    @elseif($data->form_field->type == 'checkbox')
                        <tr>
                            <th class="vertical-student-data">{{ $data->form_field->name }} :</th>
                            <td>{!! implode(",",json_decode($data->data ?? '[]')) !!}</td>
                        </tr>
                    @endif
                @endforeach

                <tr>
                    <td></td>
                    <td>
                        @if ($settings['signature'] ?? '')
                            <img class="" height="40" class="signature" width="100" align="center" src="{{ public_path('storage/').$settings['signature'] }}" alt="">
                            <span style="position: fixed;bottom:25px;right:40px"><b>Signature</b></span>
                        @endif
                    </td>
                </tr>
                
                <!-- Barcode Section - Vertical Layout -->
                <tr class="barcode-row">
                    <td colspan="2">
                        <div class="barcode-container">
                            @php $grNumber = $student->student->admission_no ?? ''; @endphp
                            <img src="{{ generateCode128Barcode($grNumber) }}" alt="{{ $grNumber }}" style="width:100%;height:25px;"/>
                            <div class="barcode-number">{{ $grNumber }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        @endif
        <div class="footer">
            <span class="footer-text" style="padding-right:10px;">Valid Until {{ $valid_until }}</span>
        </div>
        @if (isset($settings['layout_type']) && $settings['layout_type'] == 'horizontal')
            <div class="background-image">
                @if ($settings['background_image'] ?? '')
                    <img src="{{ public_path('storage/').$settings['background_image'] }}" class="background_image" height="140" width="360" alt="">
                    
                @endif
            </div>
        @else
            <div class="background-image">
                @if ($settings['background_image'] ?? '')
                    <img src="{{ public_path('storage/').$settings['background_image'] }}" class="background_image" height="140" width="280" alt="">
                    
                @endif
            </div>
        @endif
        
        
    </div>
    @endforeach
</body>
</html>