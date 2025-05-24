/**
 * CEIT Guidance Office - Admin Dashboard Handler
 * This file handles all the interactive functionality for the admin dashboard
 */

// Global variable for chart instances
let departmentChart = null;
let courseChart = null;
let userChart = null;

// Active/selected department ID
let activeDepartmentId = null;

// Store global filter state
const filters = {
    start_date: null,
    end_date: null,
    department_id: null,
    status: null
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tabs
    const tabsElement = document.getElementById('dashboardTabs');
    if (tabsElement) {
        const tabs = new bootstrap.Tab(tabsElement);
    }
    
    // Initialize user chart
    initUserChart();
    
    // Initialize filters and event listeners
    initFilters();
    
    // Load initial department data
    loadDepartmentData();
    
    // Add hash to URL when tab changes
    const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabLinks.forEach(tabLink => {
        tabLink.addEventListener('shown.bs.tab', function(event) {
            const id = event.target.getAttribute('data-bs-target');
            if (id) {
                history.pushState(null, null, id);
               
                // Load appropriate data based on active tab
                if (id === '#incident-reports') {
                    loadDepartmentData();
                } else if (id === '#user-analytics') {
                    // If we have a user chart instance, update it
                    if (userChart) {
                        userChart.update();
                    }
                }
                
                // Trigger resize event to fix chart render issues
                window.dispatchEvent(new Event('resize'));
            }
        });
    });
    
    // Back to departments button click
    document.addEventListener('click', function(e) {
        if (e.target.closest('.back-to-departments')) {
            showDepartmentsView();
        }
    });
});

/**
 * Initialize the user distribution chart
 */
function initUserChart() {
    const userChartCanvas = document.getElementById('userChart');
    if (!userChartCanvas) return;
    
    // Collect user data from PHP
    const userLabels = [];
    const userData = [];
    
    document.querySelectorAll('.analytics-table tbody tr').forEach(row => {
        const userType = row.querySelector('td:first-child').textContent;
        const countBadge = row.querySelector('.count-badge');
        if (countBadge) {
            const count = parseInt(countBadge.textContent);
            userLabels.push(userType);
            userData.push(count);
        }
    });
    
    // Generate colors
    const backgroundColors = [
        'rgba(178, 251, 165, 0.9)',
        'rgba(166, 233, 153, 0.9)',
        'rgba(153, 216, 142, 0.9)',
        'rgba(141, 198, 130, 0.9)',
        'rgba(128, 181, 119, 0.9)',
        'rgba(116, 163, 107, 0.9)',
        'rgba(104, 146, 95, 0.9)'
    ];
    
    const borderColors = backgroundColors.map(color => color.replace('0.9', '1'));
    
    const ctx = userChartCanvas.getContext('2d');
    userChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: userLabels,
            datasets: [{
                label: 'Number of Users',
                data: userData,
                backgroundColor: backgroundColors,
                borderColor: borderColors,
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 20,
                    bottom: 20
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        precision: 0  // Ensure whole numbers
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            },
            plugins: {
                legend: {
                    display: false // Remove legend since we only have one dataset
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#2c3e50',
                    bodyColor: '#2c3e50',
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return `Users: ${Math.round(context.parsed.y)}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Initialize filter controls and their event listeners
 */
function initFilters() {
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const departmentSelect = document.getElementById('department_id');
    const statusSelect = document.getElementById('status_filter');
    const resetButton = document.getElementById('resetFilters');
    
    // Date validation and auto filtering
    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', function() {
            filters.start_date = this.value;
            
            // Ensure end date is not before start date
            if (endDateInput.value && this.value > endDateInput.value) {
                endDateInput.value = this.value;
                filters.end_date = this.value;
            }
            
            endDateInput.min = this.value;
            if (filters.department_id) {
                loadCourseData(filters.department_id);
            } else {
                loadDepartmentData();
            }
        });
        
        endDateInput.addEventListener('change', function() {
            filters.end_date = this.value;
            
            // Ensure start date is not after end date
            if (startDateInput.value && this.value < startDateInput.value) {
                startDateInput.value = this.value;
                filters.start_date = this.value;
            }
            
            if (filters.department_id) {
                loadCourseData(filters.department_id);
            } else {
                loadDepartmentData();
            }
        });
    }
    
    // Status selection
    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            console.log("Status changed to:", this.value);
            filters.status = this.value;
            
            // Immediately reload data based on current view
            if (filters.department_id) {
                loadCourseData(filters.department_id);
            } else {
                loadDepartmentData();
            }
        });
    }
    
    // Department selection
    if (departmentSelect) {
        departmentSelect.addEventListener('change', function() {
            const deptId = this.value;
            filters.department_id = deptId;
            
            if (deptId) {
                loadCourseData(deptId);
            } else {
                loadDepartmentData();
            }
        });
    }
    
    // Reset button
    if (resetButton) {
        resetButton.addEventListener('click', function() {
            // Reset filter inputs
            if (startDateInput) startDateInput.value = '';
            if (endDateInput) endDateInput.value = '';
            if (departmentSelect) departmentSelect.value = '';
            if (statusSelect) statusSelect.value = '';
            
            // Reset filter state
            filters.start_date = null;
            filters.end_date = null;
            filters.department_id = null;
            filters.status = null;
            
            // Reset view and load department data
            showDepartmentsView();
            loadDepartmentData();
        });
    }
}

/**
 * Load department data from the server
 */
function loadDepartmentData() {
    showLoading(true);
    
    // Reset view to departments
    activeDepartmentId = null;
    
    // Prepare request
    const data = new FormData();
    if (filters.start_date) data.append('start_date', filters.start_date);
    if (filters.end_date) data.append('end_date', filters.end_date);
    if (filters.status) data.append('status', filters.status);
    
    console.log("Loading departments with filters:", {
        start_date: filters.start_date,
        end_date: filters.end_date,
        status: filters.status
    });
    
    // Fetch data via AJAX
    fetch('fetch_departments_data.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log("Received department data:", data.departments);
            // Update UI with department data
            updateDepartmentUI(data.departments);
        } else {
            console.error('Error fetching department data:', data.error);
            showErrorMessage('department');
        }
    })
    .catch(error => {
        console.error('AJAX error:', error);
        showErrorMessage('department');
    })
    .finally(() => {
        showLoading(false);
    });
}

/**
 * Load course data for a specific department
 * @param {number} departmentId The ID of the department to load courses for
 */
function loadCourseData(departmentId) {
    showLoading(true);
    
    // Set active department
    activeDepartmentId = departmentId;
    
    // Prepare request
    const data = new FormData();
    data.append('department_id', departmentId);
    if (filters.start_date) data.append('start_date', filters.start_date);
    if (filters.end_date) data.append('end_date', filters.end_date);
    if (filters.status) data.append('status', filters.status);
    
    console.log("Loading courses with filters:", {
        department_id: departmentId,
        start_date: filters.start_date,
        end_date: filters.end_date,
        status: filters.status
    });
    
    // Fetch data via AJAX
    fetch('fetch_courses_data.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log("Received course data:", data.courses);
            // Update UI with course data
            updateCourseUI(data.department, data.courses);
        } else {
            console.error('Error fetching course data:', data.error);
            showErrorMessage('course');
        }
    })
    .catch(error => {
        console.error('AJAX error:', error);
        showErrorMessage('course');
    })
    .finally(() => {
        showLoading(false);
    });
}

/**
 * Show or hide the loading indicator
 * @param {boolean} show Whether to show or hide the loading indicator
 */
function showLoading(show) {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const reportContent = document.getElementById('reportContent');
    
    if (loadingIndicator && reportContent) {
        if (show) {
            loadingIndicator.style.display = 'block';
            reportContent.style.display = 'none';
        } else {
            loadingIndicator.style.display = 'none';
            reportContent.style.display = 'block';
        }
    }
}

/**
 * Show an error message when data fetching fails
 * @param {string} viewType Either 'department' or 'course'
 */
function showErrorMessage(viewType) {
    const targetElement = viewType === 'department' 
        ? document.querySelector('#departmentTable tbody') 
        : document.querySelector('#courseTable tbody');
    
    if (targetElement) {
        targetElement.innerHTML = `
            <tr>
                <td colspan="${viewType === 'department' ? 3 : 2}" class="no-data-message">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Failed to load data. Please try again.
                </td>
            </tr>
        `;
    }
    
    // Clear chart
    if (viewType === 'department') {
        if (departmentChart) {
            departmentChart.destroy();
            departmentChart = null;
        }
    } else {
        if (courseChart) {
            courseChart.destroy();
            courseChart = null;
        }
    }
}

/**
 * Update the department UI with data
 * @param {Array} departments The departments data
 */
function updateDepartmentUI(departments) {
    // Show department view
    showDepartmentsView();
    
    // Update department table
    const tableBody = document.querySelector('#departmentTable tbody');
    if (tableBody) {
        if (departments.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="3" class="no-data-message">
                        No data available for the selected period or status
                    </td>
                </tr>
            `;
        } else {
            tableBody.innerHTML = '';
            departments.forEach(dept => {
                // Ensure report_count is an integer
                const reportCount = parseInt(dept.report_count);
                
                const row = document.createElement('tr');
                row.className = 'department-row';
                row.innerHTML = `
                    <td>${escapeHTML(dept.name)}</td>
                    <td><span class="count-badge">${reportCount}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary view-courses" data-dept-id="${dept.id}">
                            View Courses
                        </button>
                    </td>
                `;
                // Add click handler for "View Courses" button
                row.querySelector('.view-courses').addEventListener('click', function() {
                    const deptId = this.getAttribute('data-dept-id');
                    if (deptId) {
                        // Update department select
                        const deptSelect = document.getElementById('department_id');
                        if (deptSelect) {
                            deptSelect.value = deptId;
                            filters.department_id = deptId;
                        }
                        loadCourseData(deptId);
                    }
                });
                tableBody.appendChild(row);
            });
        }
    }
    
    // Update or create department chart
    renderDepartmentChart(departments);
}

/**
 * Update the course UI with data
 * @param {Object} department The department data
 * @param {Array} courses The courses data
 */
function updateCourseUI(department, courses) {
    // Show course view
    showCoursesView();
    
    // Update course table
    const tableBody = document.querySelector('#courseTable tbody');
    if (tableBody) {
        if (courses.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="2" class="no-data-message">
                        No data available for the selected period or status
                    </td>
                </tr>
            `;
        } else {
            tableBody.innerHTML = '';
            courses.forEach(course => {
                // Ensure report_count is an integer
                const reportCount = parseInt(course.report_count);
                
                const row = document.createElement('tr');
                row.className = 'course-row';
                row.innerHTML = `
                    <td>${escapeHTML(course.name)}</td>
                    <td><span class="count-badge">${reportCount}</span></td>
                `;
                tableBody.appendChild(row);
            });
        }
    }
    
    // Update department name and report count
    document.getElementById('selectedDepartmentName').textContent = `${department.name}`;
    document.getElementById('departmentReportCount').textContent = parseInt(department.report_count);
    
    // Update or create course chart
    renderCourseChart(courses);
}

/**
 * Show the departments view
 */
function showDepartmentsView() {
    const departmentView = document.getElementById('departmentView');
    const courseView = document.getElementById('courseView');
    
    if (departmentView && courseView) {
        // Animation classes
        courseView.classList.add('fadeOut');
        
        // After animation, hide course view and show department view
        setTimeout(() => {
            courseView.style.display = 'none';
            courseView.classList.remove('fadeOut');
            
            departmentView.style.display = 'block';
            departmentView.classList.add('fadeIn');
            
            // Remove animation class after it completes
            setTimeout(() => {
                departmentView.classList.remove('fadeIn');
            }, 400);
        }, 200);
    }
}

/**
 * Show the courses view
 */
function showCoursesView() {
    const departmentView = document.getElementById('departmentView');
    const courseView = document.getElementById('courseView');
    
    if (departmentView && courseView) {
        // Animation classes
        departmentView.classList.add('fadeOut');
        
        // After animation, hide department view and show course view
        setTimeout(() => {
            departmentView.style.display = 'none';
            departmentView.classList.remove('fadeOut');
            
            courseView.style.display = 'block';
            courseView.classList.add('fadeIn');
            
            // Remove animation class after it completes
            setTimeout(() => {
                courseView.classList.remove('fadeIn');
            }, 400);
        }, 200);
    }
}

/**
 * Render the department chart with acronyms
 * @param {Array} departments The departments data
 */
function renderDepartmentChart(departments) {
    const canvas = document.getElementById('departmentChart');
    if (!canvas) return;
    
    // Destroy previous chart instance if it exists
    if (departmentChart) {
        departmentChart.destroy();
    }
    
    if (!departments || departments.length === 0) {
        canvas.parentNode.innerHTML = `
            <div class="no-data-message">
                <i class="bi bi-bar-chart"></i>
                No data available for the selected period
            </div>
        `;
        return;
    }
    
    // Prepare data for chart - extract acronyms inside parentheses
    const fullNames = departments.map(dept => dept.name);
    const labels = departments.map(dept => {
        const match = dept.name.match(/\(([^)]+)\)/); // Regex to extract acronym inside parentheses
        return match ? match[1] : dept.name; // Fallback to full name if no acronym found
    });
    const counts = departments.map(dept => parseInt(dept.report_count));

    const backgroundColors = generateGradientColors(departments.length);

    departmentChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Incident Reports',
                data: counts,
                backgroundColor: backgroundColors,
                borderColor: backgroundColors.map(color => color.replace('0.9', '1')),
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 20,
                    bottom: 20
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        precision: 0,
                        stepSize: 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12,
                            weight: 'bold'
                        },
                        maxRotation: 0,
                        minRotation: 0
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#2c3e50',
                    bodyColor: '#2c3e50',
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            const index = context[0].dataIndex;
                            return fullNames[index];
                        },
                        label: function(context) {
                            return `Reports: ${Math.round(context.parsed.y)}`;
                        }
                    }
                }
            }
        }
    });
}


/**
 * Render the course chart with acronyms
 * @param {Array} courses The courses data
 */
function renderCourseChart(courses) {
    const canvas = document.getElementById('courseChart');
    if (!canvas) return;
    
    // Destroy previous chart instance if it exists
    if (courseChart) {
        courseChart.destroy();
    }
    
    if (!courses || courses.length === 0) {
        canvas.parentNode.innerHTML = `
            <div class="no-data-message">
                <i class="bi bi-bar-chart"></i>
                No data available for the selected period
            </div>
        `;
        return;
    }
    
    // Prepare data for chart - use abbreviated names for labels due to space constraints
    const fullNames = courses.map(course => course.name);
    // Limit label length for chart display
    const labels = courses.map(course => {
        // Try to show a meaningful short version of the course name
        const nameWithoutBS = course.name.replace(/BS |Bachelor of Science in /gi, '').trim();
        // Cut if still too long
        return nameWithoutBS.length > 20 ? nameWithoutBS.substring(0, 17) + '...' : nameWithoutBS;
    });
    const counts = courses.map(course => parseInt(course.report_count)); // Convert to integers
    
    // Generate colors
    const backgroundColors = generateGradientColors(courses.length, 'green');
    
    // Create new chart
    courseChart = new Chart(canvas, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Incident Reports',
                data: counts,
                backgroundColor: backgroundColors,
                borderColor: backgroundColors.map(color => color.replace('0.9', '1')),
                borderWidth: 2,
                borderRadius: 8,
                barThickness: 40
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 10,
                    right: 10,
                    top: 20,
                    bottom: 20
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        precision: 0,     // Ensure whole numbers
                        stepSize: 1       // Set step size to 1
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Inter', sans-serif",
                            size: 12
                        },
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }, 
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(255, 255, 255, 0.95)',
                    titleColor: '#2c3e50',
                    bodyColor: '#2c3e50',
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 1,
                    padding: 10,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            // Show full course name in tooltip title
                            const index = context[0].dataIndex;
                            return fullNames[index];
                        },
                        label: function(context) {
                            return `Reports: ${Math.round(context.parsed.y)}`;  // Use Math.round for whole numbers
                        }
                    }
                }
            }
        }
    });
}

/**
 * Generate gradient colors for charts
 * @param {number} count Number of colors needed
 * @param {string} baseColor Base color scheme ('blue' or 'green')
 * @returns {Array} Array of color values
 */
function generateGradientColors(count, baseColor = 'blue') {
    const colors = [];
    
    if (baseColor === 'blue') {
        const baseColors = [
            'rgba(178, 251, 165, 0.9)',
            'rgba(166, 233, 153, 0.9)',
            'rgba(153, 216, 142, 0.9)',
            'rgba(141, 198, 130, 0.9)',
            'rgba(128, 181, 119, 0.9)',
            'rgba(116, 163, 107, 0.9)',
            'rgba(104, 146, 95, 0.9)'
        ];
        
        for (let i = 0; i < count; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
    } else {
        const baseColors = [
            'rgba(144, 238, 144, 0.9)',
            'rgba(121, 228, 121, 0.9)',
            'rgba(98, 219, 98, 0.9)',
            'rgba(75, 209, 75, 0.9)',
            'rgba(52, 200, 52, 0.9)',
            'rgba(29, 190, 29, 0.9)',
            'rgba(16, 160, 16, 0.9)'
        ];
        
        for (let i = 0; i < count; i++) {
            colors.push(baseColors[i % baseColors.length]);
        }
    }
    
    return colors;
}

/**
 * Escape HTML to prevent XSS
 * @param {string} unsafe Unsafe string that may contain HTML
 * @returns {string} Escaped safe string
 */
function escapeHTML(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}