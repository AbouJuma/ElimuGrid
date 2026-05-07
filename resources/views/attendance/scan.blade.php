@extends('layouts.master')

@section('title') Scan Attendance @endsection

@section('content')
<div class="content-wrapper">
    <div class="page-header">
        <h3 class="page-title"><i class="fas fa-qrcode"></i> Scan Attendance</h3>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Barcode Attendance Scanner</h4>
                    
                    <form id="scanForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Class Section</label>
                                    <select class="form-control select2" id="class_section_id" required style="width:100%;">
                                        <option value="">{{ __('select') . ' ' . __('Class') }}</option>
                                        @foreach($class_sections as $section)
                                            <option value="{{ $section->id }}" data-class="{{ $section->class->id }}">
                                                {{ $section->full_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" id="date" value="{{ date('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>GR Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="gr_number" placeholder="Enter GR Number">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-primary" onclick="openScanModal()">
                                                <i class="fas fa-qrcode"></i> Open Scanner
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-success" onclick="markAttendanceManual()">
                            <i class="fas fa-check"></i> Mark Present
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="clearMainForm()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Scans -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title"><i class="fas fa-history"></i> Recent Scans</h4>
                    <table class="table table-striped">
                        <thead>
                            <tr><th>Time</th><th>Name</th><th>GR</th><th>Roll</th><th>Status</th></tr>
                        </thead>
                        <tbody id="recentScans"><tr><td colspan="5" class="text-center">No scans</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Simple Scanner Modal -->
<div class="modal" id="scanModal" style="display:none; position:fixed; z-index:1050; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:white; margin:10% auto; padding:20px; width:500px; border-radius:5px;">
        <h4><i class="fas fa-qrcode"></i> Scan Barcode</h4>
        <p>Scan student ID card or type GR Number and press Enter</p>
        
        <input type="text" id="scanInput" class="form-control form-control-lg" style="font-size:20px; text-align:center;" placeholder="Scan here..." autocomplete="off">
        
        <div id="scanStatus" class="mt-2 badge badge-secondary">Ready</div>
        
        <div class="mt-3">
            <button type="button" class="btn btn-danger" onclick="closeScanModal()">Close</button>
            <button type="button" class="btn btn-secondary" onclick="clearScanInput()">Clear</button>
        </div>
        
        <div class="mt-3" id="scanResults"></div>
    </div>
</div>

<div id="alertBox" style="position:fixed; top:20px; right:20px; z-index:9999;"></div>

<script>
// Open scanner modal
function openScanModal() {
    console.log('Opening scanner...');
    var modal = document.getElementById('scanModal');
    modal.style.display = 'block';
    document.getElementById('scanInput').focus();
}

// Close scanner modal
function closeScanModal() {
    document.getElementById('scanModal').style.display = 'none';
}

// Clear scan input
function clearScanInput() {
    document.getElementById('scanInput').value = '';
    document.getElementById('scanInput').focus();
    document.getElementById('scanStatus').className = 'mt-2 badge badge-secondary';
    document.getElementById('scanStatus').innerText = 'Ready';
}

// Handle Enter key in scanner
$(document).ready(function() {
    $('#scanInput').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            processScan();
        }
    });
});

// Process scan
function processScan() {
    var grNumber = document.getElementById('scanInput').value.trim();
    var classId = document.getElementById('class_section_id').value;
    var date = document.getElementById('date').value;
    
    console.log('Processing scan:', grNumber, classId, date);
    
    if (!grNumber || !classId || !date) {
        alert('Please select class and date first');
        return;
    }
    
    document.getElementById('scanStatus').className = 'mt-2 badge badge-warning';
    document.getElementById('scanStatus').innerText = 'Processing...';
    
    $.ajax({
        url: '{{ route("attendance.mark-by-barcode") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            gr_number: grNumber,
            class_section_id: classId,
            date: date
        },
        success: function(response) {
            console.log('Success:', response);
            if (response.success) {
                document.getElementById('scanStatus').className = 'mt-2 badge badge-success';
                document.getElementById('scanStatus').innerText = 'Success!';
                addRecentScan(response.student);
                document.getElementById('scanInput').value = '';
                setTimeout(function() {
                    document.getElementById('scanInput').focus();
                    document.getElementById('scanStatus').className = 'mt-2 badge badge-secondary';
                    document.getElementById('scanStatus').innerText = 'Ready';
                }, 1000);
            } else {
                document.getElementById('scanStatus').className = 'mt-2 badge badge-danger';
                document.getElementById('scanStatus').innerText = 'Error: ' + response.message;
            }
        },
        error: function(xhr) {
            console.log('Error:', xhr);
            document.getElementById('scanStatus').className = 'mt-2 badge badge-danger';
            document.getElementById('scanStatus').innerText = 'Error occurred';
        }
    });
}

// Manual attendance
function markAttendanceManual() {
    var grNumber = document.getElementById('gr_number').value.trim();
    var classId = document.getElementById('class_section_id').value;
    var date = document.getElementById('date').value;
    
    if (!grNumber || !classId || !date) {
        alert('Please fill all fields');
        return;
    }
    
    $.ajax({
        url: '{{ route("attendance.mark-by-barcode") }}',
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            gr_number: grNumber,
            class_section_id: classId,
            date: date
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                addRecentScan(response.student);
                document.getElementById('gr_number').value = '';
            } else {
                alert(response.message);
            }
        },
        error: function(xhr) {
            alert('Error marking attendance');
        }
    });
}

// Add to recent scans
function addRecentScan(student) {
    var tbody = document.getElementById('recentScans');
    if (tbody.innerHTML.includes('No scans')) {
        tbody.innerHTML = '';
    }
    
    var time = new Date().toLocaleTimeString();
    var row = '<tr>' +
        '<td>' + time + '</td>' +
        '<td>' + student.name + '</td>' +
        '<td>' + student.gr_number + '</td>' +
        '<td>' + student.roll_number + '</td>' +
        '<td><span class="badge badge-success">' + student.status + '</span></td>' +
        '</tr>';
    
    tbody.insertAdjacentHTML('afterbegin', row);
}

// Clear main form
function clearMainForm() {
    document.getElementById('gr_number').value = '';
}

// Close modal when clicking outside
window.onclick = function(event) {
    var modal = document.getElementById('scanModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
@endsection
