function toggleWitnessFields(select) { 
    const witnessEntry = select.closest('.witness-entry');
    const studentFields = witnessEntry.querySelector('.student-fields');
    const staffFields = witnessEntry.querySelector('.staff-fields');
    
    // Hide all fields first
    studentFields.classList.add('hidden');
    staffFields.classList.add('hidden');
    
    // Reset all fields
    witnessEntry.querySelectorAll('input').forEach(input => {
        input.value = '';
        input.required = false;
        input.disabled = true;
    });
    
    if (select.value === 'student') {
        studentFields.classList.remove('hidden');
        witnessEntry.querySelector('input[name="witnessId[]"]').disabled = false;
        witnessEntry.querySelector('input[name="witnessId[]"]').required = true;
        witnessEntry.querySelector('input[name="witnesses[]"]').required = true;
    } else if (select.value === 'staff') {
        staffFields.classList.remove('hidden');
        staffFields.querySelectorAll('input').forEach(input => {
            input.disabled = false;
            input.required = true;
        });
    }
}

function addWitnessField() {
    const container = document.getElementById('witnessesContainer');
    const div = document.createElement('div');
    div.className = 'witness-entry';
    div.innerHTML = `
        <select class="form-control mb-2" name="witnessType[]" onchange="toggleWitnessFields(this)" required>
            <option value="">Select Witness Type</option>
            <option value="student">Student</option>
            <option value="staff">Staff</option>
        </select>
        
        <!-- Student Fields - Hidden by default -->
        <div class="row student-fields hidden">
            <div class="col-md-4">
                <input type="text" class="form-control mb-2" 
                    name="witnessId[]" 
                    placeholder="Student ID"
                    onkeyup="validateStudentIdInput(this)" 
                    onchange="fetchWitnessInfo(this)">
            </div>
            <div class="col-md-8">
                <input type="text" class="form-control mb-2" 
                    name="witnesses[]" 
                    placeholder="Student Name"
                    oninput="this.value = this.value.toUpperCase()">
            </div>
        </div>
        
        <!-- Staff Fields - Hidden by default -->
        <div class="row staff-fields hidden">
            <div class="col-md-6">
                <input type="text" class="form-control mb-2" 
                    name="staffWitnessName[]" 
                    placeholder="Staff Name"
                    oninput="this.value = this.value.toUpperCase()" 
                    required>
            </div>
            <div class="col-md-6">
                <input type="email" class="form-control mb-2" 
                    name="witnessEmail[]" 
                    placeholder="Staff Email" required>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
}

$(document).ready(function() {
    // Check for success message from PHP session
    <?php if (isset($_SESSION['report_submitted']) && $_SESSION['report_submitted']): ?>
        Swal.fire({
            title: 'Success!',
            text: 'Incident report has been submitted successfully.',
            icon: 'success',
            confirmButtonText: 'OK'
        }).then(() => {
            <?php unset($_SESSION['report_submitted']); ?>
        });
    <?php endif; ?>

    // Set max date to today
    const today = new Date().toISOString().split('T')[0];
    $('#incidentDate').attr('max', today);

    // Initial time restrictions
    updateTimeRestrictions();

    // Update time restrictions when date changes
    $('#incidentDate').on('change', function() {
        updateTimeRestrictions();
        // Reset time when date changes
        $('#incidentTime').val('');
    });

    // Time input handler
    $('#incidentTime').on('input', function() {
        const selectedDate = $('#incidentDate').val();
        const selectedTime = $(this).val();
        
        if (selectedDate === today) {
            const now = new Date();
            const currentTime = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
            
            if (selectedTime > currentTime) {
                $(this).val('');
                Swal.fire({
                    title: 'Invalid Time',
                    text: 'You cannot select a future time for today\'s date.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
            }
        }
        updateCombinedField();
    });

    // Update combined field when any relevant input changes
    $('#incidentPlace, #incidentDate, #incidentTime').on('change', function() {
        updateCombinedField();
    });

    //form validation
   $('#incidentReportForm').on('submit', async function(e) {
    e.preventDefault();

    // Enable all disabled fields temporarily for form submission
    const disabledFields = $(this).find(':disabled').removeAttr('disabled');
    
    const studentIds = [];
    let hasValidStudent = false;

    // Collect all non-empty student IDs from persons involved
    $('input[name="personsInvolvedId[]"]').each(function() {
        if ($(this).val()) {
            studentIds.push($(this).val());
        }
    });

    // Rest of your validation code...

    // If validation passes, show confirmation dialog
    Swal.fire({
        title: 'Confirm Submission',
        text: 'Are you sure you want to submit this incident report?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, submit it!'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData(this);
            
            // Re-disable the fields after getting their values
            disabledFields.attr('disabled', 'disabled');
            
            $.ajax({
                url: $(this).attr('action') || window.location.href,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: result.message || 'Incident report has been submitted successfully.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                $('#incidentReportForm')[0].reset();
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error',
                                text: result.message || 'An error occurred while submitting the report.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        console.log('Response:', response); // Add for debugging
                        Swal.fire({
                            title: 'Error',
                            text: 'An unexpected error occurred.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    console.log('Status:', status);
                    console.log('Response:', xhr.responseText);
                    Swal.fire({
                        title: 'Error',
                        text: 'An error occurred while submitting the report.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        } else {
            // Re-disable the fields if submission is cancelled
            disabledFields.attr('disabled', 'disabled');
        }
    });
});

});

function updateTimeRestrictions() {
    const dateInput = document.getElementById('incidentDate');
    const timeInput = document.getElementById('incidentTime');
    const selectedDate = dateInput.value;
    const now = new Date();
    const today = now.toISOString().split('T')[0];

    // If selected date is today, restrict time input
    if (selectedDate === today) {
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        timeInput.setAttribute('max', `${hours}:${minutes}`);
        
        // If current time selection is later than current time, reset it
        if (timeInput.value > `${hours}:${minutes}`) {
            timeInput.value = '';
        }
    } else {
        timeInput.removeAttribute('max');
    }
}

function updateCombinedField() {
    const place = $('#incidentPlace').val();
    const date = $('#incidentDate').val();
    const time = $('#incidentTime').val();
    
    if (place && date && time) {
        const selectedDate = new Date(date + 'T' + time);
        const formattedDate = selectedDate.toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: 'numeric',
            minute: 'numeric',
            hour12: true
        });
        $('#place').val(`${place} - ${formattedDate}`);
    }
}

function validateStudentId(studentId) {
    return new Promise((resolve) => {
        if (!studentId) {
            resolve(true);
            return;
        }

        $.ajax({
            url: 'check_student.php',
            method: 'POST',
            data: { student_id: studentId },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (!result.exists) {
                        Swal.fire({
                            title: 'Invalid Student ID',
                            text: 'This student is not part of the CEIT Population. Please remove this entry or provide a valid CEIT student ID.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                    resolve(result.exists);
                } catch (e) {
                    resolve(false);
                }
            },
            error: function() {
                resolve(false);
            }
        });
    });
}

function addPersonInvolved() {
    var container = document.getElementById('personsInvolvedContainer');
    var div = document.createElement('div');
    div.className = 'person-involved-entry';
    div.innerHTML = `
        <div class="row">
            <div class="col-md-4">
                <input type="text" class="form-control mb-2" 
                    name="personsInvolvedId[]" 
                    placeholder="Student ID (Optional)" 
                    onkeyup="validateStudentIdInput(this)" 
                    onchange="fetchStudentInfo(this)">
            </div>
            <div class="col-md-8">
                <input type="text" class="form-control mb-2" 
                    name="personsInvolved[]" 
                    placeholder="Name" 
                    oninput="this.value = this.value.toUpperCase()"
                    required>
            </div>
        </div>
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
}


// Also update the toggleStudentIdField function to clear the name when switching types:
function toggleStudentIdField(select) {
    const witnessEntry = select.closest('.witness-entry');
    const idField = witnessEntry.querySelector('input[name="witnessId[]"]');
    const nameField = witnessEntry.querySelector('input[name="witnesses[]"]');
    
    idField.value = ''; // Clear the ID field
    nameField.value = ''; // Clear the name field
    idField.disabled = select.value !== 'student';
}


function toggleWitnessFields(select) {
    const witnessEntry = select.closest('.witness-entry');
    const studentFields = witnessEntry.querySelector('.student-fields');
    const staffFields = witnessEntry.querySelector('.staff-fields');
    
    // Hide all fields first
    studentFields.classList.add('hidden');
    staffFields.classList.add('hidden');
    
    // Reset all fields
    witnessEntry.querySelectorAll('input').forEach(input => {
        input.value = '';
        input.required = false;
        input.disabled = false; // Don't disable by default
    });
    
    if (select.value === 'student') {
        studentFields.classList.remove('hidden');
        const studentNameInput = witnessEntry.querySelector('input[name="witnesses[]"]');
        studentNameInput.required = true;  // Only name is required
    } else if (select.value === 'staff') {
        staffFields.classList.remove('hidden');
        staffFields.querySelectorAll('input').forEach(input => {
            input.disabled = false;
            input.required = true;
        });
    }
}

function fetchWitnessInfo(input) {
    const witnessEntry = input.closest('.witness-entry');
    const witnessType = witnessEntry.querySelector('select[name="witnessType[]"]').value;
    
    if (witnessType !== 'student') return;
    
    const studentId = input.value;
    const nameInput = witnessEntry.querySelector('input[name="witnesses[]"]');
    
    if (isDuplicate(studentId, 'studentId', input)) {
        Swal.fire({
            title: 'Duplicate Entry',
            text: 'This Student ID has already been used. Please use a different ID.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        input.value = '';
        nameInput.value = '';
        nameInput.disabled = false;  // Enable name input when clearing
        return;
    }
    
    // Enable name field by default
    nameInput.disabled = false;
    
    // Clear and enable name if ID is empty
    if (!studentId) {
        nameInput.value = '';
        return;
    }
    
    // If ID is not complete (9 digits), clear name and enable field
    if (studentId.length < 9) {
        nameInput.value = '';
        return;
    }

    // Query database only when we have a complete student ID
    $.ajax({
        url: 'get_student_info.php',
        method: 'POST',
        data: { student_id: studentId },
        success: function(response) {
            try {
                const result = JSON.parse(response);
                if (result.success) {
                    nameInput.value = result.name;
                    nameInput.disabled = true;  // Disable only when valid student found
                } else {
                    nameInput.value = '';
                    nameInput.disabled = false;  // Enable if student not found
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                nameInput.disabled = false;
            }
        },
        error: function() {
            console.error('Error fetching student info');
            nameInput.value = '';
            nameInput.disabled = false;
        }
    });
}


// Add this function to check for duplicates
function isDuplicate(value, inputType, currentInput) {
    // If value is empty, it's not a duplicate
    if (!value || value.trim() === '') {
        return false;
    }

    let isDuplicate = false;
    
    // Only check for student ID duplicates
    if (inputType === 'studentId') {
        // Check in persons involved
        $('input[name="personsInvolvedId[]"]').each(function() {
            if ($(this).val() === value && this !== currentInput) {
                isDuplicate = true;
                return false; // break the loop
            }
        });
        
        // Also check in witness student IDs
        if (!isDuplicate) {
            $('input[name="witnessId[]"]').each(function() {
                if ($(this).val() === value && this !== currentInput) {
                    isDuplicate = true;
                    return false;
                }
            });
        }
    }
    
    return isDuplicate;
}

function fetchStudentInfo(input) {
    const studentId = input.value;
    const nameInput = input.closest('.person-involved-entry').querySelector('input[name="personsInvolved[]"]');
    
    if (isDuplicate(studentId, 'studentId', input)) {
        Swal.fire({
            title: 'Duplicate Entry',
            text: 'This Student ID has already been used. Please use a different ID.',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        input.value = '';
        nameInput.value = '';
        nameInput.disabled = false;
        return;
    }
    
    if (studentId) {
        $.ajax({
            url: 'get_student_info.php',
            method: 'POST',
            data: { student_id: studentId },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    if (result.success) {
                        nameInput.value = result.name;
                        nameInput.disabled = true;  // Disable only when valid student found
                    } else {
                        nameInput.value = '';
                        nameInput.disabled = false;  // Enable if student not found
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    nameInput.disabled = false;
                }
            },
            error: function() {
                console.error('Error fetching student info');
                nameInput.disabled = false;
            }
        });
    } else {
        nameInput.value = '';
        nameInput.disabled = false;  // Enable if no student ID
    }
}

function removeEntry(button) {
    button.closest('.person-involved-entry, .witness-entry').remove();
}


function validateStudentIdInput(input) {
        let value = input.value.replace(/[^0-9]/g, '');
        if (value.length > 9) {
            value = value.slice(0, 9);
        }
        input.value = value;
    }

