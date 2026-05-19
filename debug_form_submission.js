// Debug script to identify where form submission hangs
console.log('=== School Form Debug Script Loaded ===');

$(document).ready(function() {
    console.log('Document ready');
    
    // Override form submission to add debugging
    $('.school-registration-validate').off('submit').on('submit', function(e) {
        console.log('=== FORM SUBMISSION STARTED ===');
        console.log('Time:', new Date().toISOString());
        
        // Prevent default to handle manually
        e.preventDefault();
        
        // Check if form is valid
        if (!$('.school-registration-validate').valid()) {
            console.log('❌ Form validation failed');
            console.log('Validation errors:', $('.school-registration-validate').validate().errorList);
            return false;
        }
        
        console.log('✅ Form validation passed');
        
        // Show loading
        console.log('🔄 Showing loading...');
        showLoading();
        
        // Get form data
        var formData = new FormData(this);
        console.log('📝 Form data prepared');
        
        // Log form data (without files for console readability)
        var formDataObj = {};
        for (var pair of formData.entries()) {
            if (pair[1] instanceof File) {
                formDataObj[pair[0]] = '[FILE: ' + pair[1].name + ']';
            } else {
                formDataObj[pair[0]] = pair[1];
            }
        }
        console.log('Form data:', formDataObj);
        
        // Make AJAX request with detailed logging
        console.log('🚀 Starting AJAX request...');
        var startTime = performance.now();
        
        $.ajax({
            url: '/schools',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function(xhr) {
                console.log('📤 Request about to be sent');
                console.log('URL:', xhr.url);
                console.log('Headers:', xhr);
            },
            success: function(data, textStatus, jqXHR) {
                var endTime = performance.now();
                console.log('✅ SUCCESS!');
                console.log('⏱️ Request time:', (endTime - startTime) + 'ms');
                console.log('Response:', data);
                console.log('Status:', textStatus);
                console.log('XHR:', jqXHR);
                
                closeLoading();
                
                if (data.code == 200) {
                    console.log('🎉 Success response received');
                    showSuccessToast(data.message);
                    setTimeout(function() {
                        console.log('🔄 Reloading page...');
                        window.location.reload();
                    }, 2000);
                } else {
                    console.log('❌ Error in response');
                    showErrorToast(data.message);
                }
            },
            error: function(xhr, status, error) {
                var endTime = performance.now();
                console.log('❌ ERROR!');
                console.log('⏱️ Request time:', (endTime - startTime) + 'ms');
                console.log('Status:', status);
                console.log('Error:', error);
                console.log('XHR:', xhr);
                console.log('Response Text:', xhr.responseText);
                console.log('Response Headers:', xhr.getAllResponseHeaders());
                
                closeLoading();
                showErrorToast('Error occurred: ' + error);
            },
            complete: function(xhr, status) {
                var endTime = performance.now();
                console.log('🏁 REQUEST COMPLETE');
                console.log('⏱️ Total time:', (endTime - startTime) + 'ms');
                console.log('Final status:', status);
            }
        });
        
        console.log('🔥 AJAX request initiated');
        return false;
    });
    
    // Also add click handler for submit button to see if it's being triggered
    $('#create-btn').off('click').on('click', function(e) {
        console.log('=== SUBMIT BUTTON CLICKED ===');
        console.log('Time:', new Date().toISOString());
        
        // Check if form is valid before submission
        if (!$('.school-registration-validate').valid()) {
            console.log('❌ Form not valid on button click');
            console.log('Validation errors:', $('.school-registration-validate').validate().errorList);
            return false;
        }
        
        console.log('✅ Form valid, triggering submit');
        $('.school-registration-validate').submit();
    });
    
    console.log('🎯 Debug handlers attached');
});
