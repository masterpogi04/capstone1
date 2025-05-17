// Add this new function first
function isStudentIdDuplicate(studentId, excludeElement = null) {
    let isDuplicate = false;
    
    // Check persons involved
    const personsInvolvedIds = document.querySelectorAll('.person-involved-entry .student-id');
    personsInvolvedIds.forEach(input => {
        if (input !== excludeElement && input.value === studentId) {
            isDuplicate = true;
        }
    }); 
    
    // Check witnesses
    const witnessIds = document.querySelectorAll('.witness-entry .student-id');
    witnessIds.forEach(input => {
        if (input !== excludeElement && input.value === studentId) {
            isDuplicate = true;
        }
    });
    
    return isDuplicate;
}

function isEmailDuplicate(email, excludeElement = null) {
    let isDuplicate = false;
    const staffEmails = document.querySelectorAll('.witness-entry .staff-field');
    staffEmails.forEach(input => {
        if (input !== excludeElement && input.value === email && email !== '') {
            isDuplicate = true;
        }
    });
    return isDuplicate;
}

// Then replace these three existing functions:

function removeEntry(button) {
    button.closest('.person-involved-entry, .witness-entry').remove();
}

function toggleWitnessFields(select) {
    var entry = select.closest('.witness-entry');
    var studentFields = entry.querySelectorAll('.student-field');
    var staffFields = entry.querySelectorAll('.staff-field');
    var nameField = entry.querySelector('.witness-name');
    var emailField = entry.querySelector('[name="witnessEmail[]"]');
    var studentIdField = entry.querySelector('.student-id');
    
    // First, hide and clear everything, remove required attributes
    studentFields.forEach(field => {
        field.style.display = 'none';
        field.value = '';
        field.required = false;
        field.readOnly = false; // Ensure fields are not readonly
    });
    staffFields.forEach(field => {
        field.style.display = 'none';
        field.value = '';
        field.required = false;
    });
    nameField.style.display = 'none';
    nameField.value = '';
    nameField.required = false;
    nameField.readOnly = false; // Ensure name field is not readonly initially
    
    if (select.value === "") {
        // Keep everything hidden for empty selection
        return;
    } else if (select.value === 'student') {
        studentFields.forEach(field => field.style.display = 'block');
        nameField.style.display = 'block';
        nameField.readOnly = false;
        nameField.required = false;  // Make witness name optional
        studentIdField.required = false;  // Student ID is optional
        $(emailField).off('blur');
    } else if (select.value === 'staff') {
        staffFields.forEach(field => {
            field.style.display = 'block';
            field.required = false;  // Make staff email optional
        });
        nameField.style.display = 'block';
        nameField.readOnly = false;
        nameField.required = false;  // Make staff name optional
        attachEmailListener(emailField);
    }
}

function addPersonInvolved() {
    var container = document.getElementById('personsInvolvedContainer');
    var div = document.createElement('div');
    div.className = 'person-involved-entry';
    div.innerHTML = `
        <input type="text" 
            class="form-control mb-2 student-id" 
            name="personsInvolvedId[]" 
            placeholder="Student ID (Optional)" 
            oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);">
        <input type="text" 
            class="form-control mb-2 student-name" 
            name="personsInvolved[]"
            required 
            placeholder="Name" 
            oninput="this.value = this.value.toUpperCase();">
        <input type="text" class="form-control mb-2 student-year-course" name="personsInvolvedYearCourse[]" placeholder="Year & Course">
        <input type="text" class="form-control mb-2" name="personsInvolvedAdviser[]" placeholder="Registration Adviser">
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
    attachStudentIdListener(div.querySelector('.student-id'));
}

function addWitnessField() {
    var container = document.getElementById('witnessesContainer');
    var div = document.createElement('div');
    div.className = 'witness-entry';
    div.innerHTML = `
        <select class="form-control mb-2 witness-type" name="witnessType[]" onchange="toggleWitnessFields(this)">
            <option value="">Select Witness Type</option>
            <option value="student">Student</option>
            <option value="staff">Staff</option>
        </select>
        <input type="text" 
            class="form-control mb-2 student-field student-id" 
            name="witnessId[]" 
            placeholder="Student ID" 
            style="display:none;"
            oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 9);">
        <input type="text" 
            class="form-control mb-2 witness-name" 
            name="witnesses[]" 
            placeholder="Name"
            style="display:none;"
            oninput="this.value = this.value.toUpperCase();">
        <input type="text" class="form-control mb-2 student-field student-year-course" name="witnessesYearCourse[]" placeholder="Year & Course" style="display:none;">
        <input type="text" class="form-control mb-2 student-field" name="witnessesAdviser[]" placeholder="Registration Adviser" style="display:none;">
        <input type="email" class="form-control mb-2 staff-field" name="witnessEmail[]" placeholder="Email" style="display:none;">
        <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeEntry(this)">
            <i class="fas fa-trash"></i> Remove
        </button>
    `;
    container.appendChild(div);
    attachStudentIdListener(div.querySelector('.student-id'));
}

function attachEmailListener(input) {
    $(input).on('blur', function() {
        var email = $(this).val();
        var entry = $(this).closest('.witness-entry');
        
        if (email && isEmailDuplicate(email, this)) {
            Swal.fire({
                title: 'Duplicate Email',
                text: 'This email has already been added as a witness.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            // Only clear the email field, keep the name
            $(this).val('');
        }
    });
}

function validateTime() {
    var selectedDate = new Date($('#incidentDate').val());
    var selectedTime = $('#incidentTime').val();
    var today = new Date();
    
    // Reset time parts for date comparison
    selectedDate.setHours(0, 0, 0, 0);
    var todayDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
    
    // If selected date is today, check if time is future
    if (selectedDate.getTime() === todayDate.getTime()) {
        var currentTime = today.toLocaleTimeString('en-US', { hour12: false });
        
        if (selectedTime > currentTime) {
            Swal.fire({
                title: 'Invalid Time',
                text: 'You cannot select a future time for today\'s date.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            $('#incidentTime').val('');
            return false;
        }
    }
    return true;
}

function updateCombinedField() {
    var place = $('#incidentPlace').val();
    var date = $('#incidentDate').val();
    var time = $('#incidentTime').val();
    
    if (place && date && time) {
        if (validateTime()) {
            var formattedDate = new Date(date + 'T' + time).toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                hour12: true
            });
            $('#place').val(place + ' - ' + formattedDate);
        }
    }
}

function setMaxDate() {
    var today = new Date();
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0');
    var yyyy = today.getFullYear();

    today = yyyy + '-' + mm + '-' + dd;
    $('#incidentDate').attr('max', today);
}

// Updated attachStudentIdListener function to handle optional witnesses
function attachStudentIdListener(input) {
    $(input).on('blur', function() {
        var studentId = $(this).val();
        var entry = $(this).closest('.person-involved-entry, .witness-entry');
        var nameField = entry.find('.student-name, .witness-name');
        var yearCourseField = entry.find('.student-year-course');
        var adviserField = entry.find('[name="personsInvolvedAdviser[]"], [name="witnessesAdviser[]"]');
        var isWitness = entry.hasClass('witness-entry');
        
        // If student ID is empty, allow manual name entry
        if (!studentId.trim()) {
            nameField.prop('readonly', false);
            
            // Keep name required for persons involved, but not for witnesses
            if (!isWitness) {
                nameField.prop('required', true);
            } else {
                nameField.prop('required', false);
            }
            
            yearCourseField.val('');
            adviserField.val('');
            if (isWitness) {
                entry.find('[name="witnessId[]"]').val('');
            }
            return;
        }
        
        // If student ID is provided for a person involved, make name field required
        // For witnesses, it remains optional
        if (!isWitness) {
            nameField.prop('required', true);
        }
        
        // Check for duplicates only if student ID is not empty
        if (studentId && isStudentIdDuplicate(studentId, this)) {
            Swal.fire({
                title: 'Duplicate Entry',
                text: 'This student ID has already been added to the form.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            // Clear the ID field and related fields, keep the name
            $(this).val('');
            yearCourseField.val('');
            adviserField.val('');
            nameField.prop('readonly', false);
            
            // Only set required for persons involved
            if (!isWitness) {
                nameField.prop('required', true);
            } else {
                nameField.prop('required', false);
            }
            return;
        }
        
        // Proceed with AJAX call only if student ID is provided
        $.ajax({
            url: 'get_student_info-facilitator.php',
            method: 'POST',
            data: { student_id: studentId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Auto-fill the fields with retrieved information
                    nameField.val(response.name)
                            .prop('readonly', true);  // Only name field should be readonly
                    
                    // Only set required for persons involved
                    if (!isWitness) {
                        nameField.prop('required', true);
                    } else {
                        nameField.prop('required', false);
                    }
                    
                    // Populate the year & course and adviser fields but keep them editable
                    yearCourseField.val(response.year_course)
                                  .prop('readonly', false);  // Make sure it's not readonly
                    
                    adviserField.val(response.adviser)
                               .prop('readonly', false);  // Make sure it's not readonly
                    
                    if (isWitness) {
                        entry.find('[name="witnessId[]"]').val(studentId);
                    }
                } else {
                    // For unregistered student IDs, keep the ID visible and allow manual name entry
                    yearCourseField.val('');
                    adviserField.val('');
                    nameField.prop('readonly', false);
                    
                    // Only set required for persons involved
                    if (!isWitness) {
                        nameField.prop('required', true);
                    } else {
                        nameField.prop('required', false);
                    }
                    
                    // Mark as unregistered
                    $(input).attr('data-unregistered', 'true');
                    
                    Swal.fire({
                        title: 'Student Not Found',
                        text: 'Student ID not found. You may manually enter the student\'s name.',
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while fetching student information. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                nameField.prop('readonly', false);
                
                // Only set required for persons involved
                if (!isWitness) {
                    nameField.prop('required', true);
                } else {
                    nameField.prop('required', false);
                }
                
                yearCourseField.prop('readonly', false);
                adviserField.prop('readonly', false);
            }
        });
    });
}

// Add this new function to check for valid students
function hasValidStudent() {
    let hasRegisteredCeitStudent = false;
    const studentFields = document.querySelectorAll('.person-involved-entry');
    
    studentFields.forEach(entry => {
        const studentId = entry.querySelector('.student-id').value;
        const yearCourse = entry.querySelector('.student-year-course').value;
        const studentName = entry.querySelector('.student-name').value;
        
        // Check if this is a registered CEIT student (has both ID and year-course)
        if (studentId && yearCourse) {
            hasRegisteredCeitStudent = true;
        }
    });
    
    // At least one person must be involved (name field not empty)
    let hasAnyPerson = false;
    studentFields.forEach(entry => {
        if (entry.querySelector('.student-name').value.trim()) {
            hasAnyPerson = true;
        }
    });
    
    return {
        hasCeitStudent: hasRegisteredCeitStudent,
        hasAnyPerson: hasAnyPerson
    };
}

// Function to reset form to initial state
function resetForm() {
    $('#incidentReportForm')[0].reset();
    // Clear all additional person/witness entries
    const personsContainer = document.getElementById('personsInvolvedContainer');
    const witnessesContainer = document.getElementById('witnessesContainer');
    
    // Keep only the first person involved entry
    while (personsContainer.children.length > 1) {
        personsContainer.removeChild(personsContainer.lastChild);
    }
    
    // Keep only the first witness entry and reset it
    while (witnessesContainer.children.length > 1) {
        witnessesContainer.removeChild(witnessesContainer.lastChild);
    }
    
    // Reset the first witness entry
    const firstWitnessEntry = witnessesContainer.firstElementChild;
    if (firstWitnessEntry) {
        const witnessType = firstWitnessEntry.querySelector('.witness-type');
        witnessType.value = '';
        const nameField = firstWitnessEntry.querySelector('.witness-name');
        nameField.value = '';
        nameField.style.display = 'none';
        
        // Hide all specific fields
        firstWitnessEntry.querySelectorAll('.student-field, .staff-field').forEach(field => {
            field.style.display = 'none';
            field.value = '';
        });
    }

    // Reset the combined place field
    $('#place').val('');
    
    // Clear any file input
    const fileInput = document.querySelector('input[type="file"]');
    if (fileInput) {
        fileInput.value = '';
    }
}

// Remove all existing form submit handlers and replace with this single one
$(document).ready(function() {
    setMaxDate();
    
    // Attach event listeners to update combined field
    $('#incidentPlace, #incidentDate').on('change', updateCombinedField);
    $('#incidentTime').on('change', function() {
        if (validateTime()) {
            updateCombinedField();
        }
    });

    // Attach listeners to initial fields
    attachStudentIdListener($('.student-id'));
    
    // Ensure all Year & Course and Adviser fields are NOT readonly on page load
    $('.student-year-course, [name="personsInvolvedAdviser[]"], [name="witnessesAdviser[]"]').prop('readonly', false);

    // Single form submission handler
    $('#incidentReportForm').off('submit').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateTime()) {
            return false;
        }
        
        const validation = hasValidStudent();
        
        if (!validation.hasAnyPerson) {
            Swal.fire({
                title: 'Missing Required Information',
                text: 'At least one person must be involved in the incident report.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        if (!validation.hasCeitStudent) {
            Swal.fire({
                title: 'Missing Required Information',
                text: 'At least one registered CEIT student must be involved in the incident report.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // Add validation for witnesses (optional field check)
        let hasIncompleteWitness = false;
        $('.witness-entry').each(function() {
            const witnessType = $(this).find('.witness-type').val();
            const witnessName = $(this).find('.witness-name').val();
            
            // If witness type is selected but name is missing
            if (witnessType && !witnessName) {
                hasIncompleteWitness = true;
            }
            
            // For staff witnesses, check if email is missing
            if (witnessType === 'staff') {
                const witnessEmail = $(this).find('[name="witnessEmail[]"]').val();
                if (!witnessEmail) {
                    hasIncompleteWitness = true;
                }
            }
        });
        
        if (hasIncompleteWitness) {
            Swal.fire({
                title: 'Incomplete Witness Information',
                text: 'If you add a witness, please complete all their information or remove the witness entry.',
                icon: 'warning',
                confirmButtonText: 'OK'
            });
            return false;
        }
        
        // If all validations pass, show confirmation dialog
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
                var formData = new FormData(this);
                
                // Clear student IDs for unregistered students before submission
                $('.person-involved-entry').each(function() {
                    var idField = $(this).find('.student-id');
                    var yearCourseField = $(this).find('.student-year-course');
                    
                    if (!yearCourseField.val() && idField.val() && idField.attr('data-unregistered') === 'true') {
                        formData.delete(idField.attr('name'));
                    }
                });
                
                $.ajax({
                    url: $(this).attr('action'),
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire(
                                'Submitted!',
                                response.message,
                                'success'
                            ).then(() => {
                                resetForm();
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    },
                    error: function() {
                        Swal.fire(
                            'Error!',
                            'An error occurred while submitting the report.',
                            'error'
                        );
                    }
                });
            }
        });
    });
});