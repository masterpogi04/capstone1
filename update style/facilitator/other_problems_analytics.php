<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

echo "<!-- Debug Info:";
echo "Self Problems Query: " . $query_self . "\n";
echo "Family Problems Query: " . $query_family . "\n";
echo "Self Problems Data: " . print_r($self_problems, true) . "\n";
echo "Family Problems Data: " . print_r($family_problems, true) . "\n";
echo "-->";

// Query to get self-reported problems
// Query for self-reported problems
$query_self = "
    SELECT 
        CASE 
            WHEN problems = '' OR problems IS NULL THEN 'NO PROBLEMS'
            ELSE TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(problems, ';', n.n), ';', -1))
        END as problem,
        COUNT(*) as count
    FROM student_profiles sp
    CROSS JOIN (
        SELECT a.N + b.N * 10 + 1 n
        FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) a,
             (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) b
        ORDER BY n
    ) n
    WHERE 
        n.n <= GREATEST(1, LENGTH(problems) - LENGTH(REPLACE(problems, ';', '')) + 1)
        OR problems IS NULL OR problems = ''
    GROUP BY problem
    HAVING problem != ''";

// Query for family problems
$query_family = "
    SELECT 
        CASE 
            WHEN family_problems = '' OR family_problems IS NULL THEN 'NO PROBLEMS'
            ELSE TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(family_problems, ';', n.n), ';', -1))
        END as problem,
        COUNT(*) as count
    FROM student_profiles sp
    CROSS JOIN (
        SELECT a.N + b.N * 10 + 1 n
        FROM (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) a,
             (SELECT 0 AS N UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4) b
        ORDER BY n
    ) n
    WHERE 
        n.n <= GREATEST(1, LENGTH(family_problems) - LENGTH(REPLACE(family_problems, ';', '')) + 1)
        OR family_problems IS NULL OR family_problems = ''
    GROUP BY problem
    HAVING problem != ''";

$self_problems = [];
$family_problems = [];

// Execute self problems query
$result = $connection->query($query_self);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $self_problems[] = $row;
    }
} else {
    error_log("Error in self problems query: " . $connection->error);
}

// Execute family problems query
$result = $connection->query($query_family);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $family_problems[] = $row;
    }
} else {
    error_log("Error in family problems query: " . $connection->error);
}

// Debug output (remove in production)
error_log("Self Problems Data: " . print_r($self_problems, true));
error_log("Family Problems Data: " . print_r($family_problems, true));

$problems_data = [
    'self' => $self_problems,
    'family' => $family_problems
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Problems Analytics</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
    
    <style>
        .chart-legend {
            display: flex;
            flex-wrap: nowrap;
            justify-content: flex-start;
            gap: 1rem;
            margin-top: 1rem;
            overflow-x: auto;
            padding-bottom: 10px;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
            white-space: nowrap;
            padding: 4px;
        }

        .chart-legend::-webkit-scrollbar {
            height: 6px;
        }

        .chart-legend::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 3px;
        }

        .chart-legend::-webkit-scrollbar-thumb {
            background-color: #cbd5e0;
            border-radius: 3px;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
            padding: 1rem;
        }

        .card {
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .loading {
            opacity: 0.5;
            pointer-events: none;
        }

        .legend-item {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8f9fa;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .legend-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a5568;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-family: Arial, sans-serif;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: #2d3748;
            color: white;
            text-decoration: none;
        }

        .back-btn::before {
            content: "←";
            margin-right: 8px;
        }

        .nav-analytics {
            background: #ffffff;
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.5rem 0;
            margin: 0 -15px 2rem;
            border-bottom: 3px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .nav-analytics .nav-item {
            margin-right: 0.5rem;
        }

        .nav-analytics .nav-link {
            color: #6c757d;
            padding: 1rem 2rem;
            border-radius: 10px 10px 0 0;
            position: relative;
            transition: all 0.3s ease;
            font-weight: 500;
            white-space: nowrap;
            border: 2px solid transparent;
            background: #f8f9fa;
        }

        .nav-analytics .nav-link:hover:not(.active) {
            color: #2196F3;
            background: #f1f4f6;
            transform: translateY(-2px);
        }

        .nav-analytics .nav-link.active {
            background: #ffffff;
            color: #2196F3;
            border: 2px solid #e9ecef;
            border-bottom: 3px solid #ffffff;
            margin-bottom: -3px;
            transform: translateY(-3px);
            box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
        }

        .nav-analytics .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: #2196F3;
            border-radius: 3px 3px 0 0;
        }

        @media (max-width: 768px) {
            .nav-analytics {
                padding: 0.5rem 1rem 0;
            }
            
            .nav-analytics .nav-link {
                padding: 0.75rem 1.25rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <a href="facilitator_dashboard.php" class="back-btn">Go Back</a>
        <h2 class="mb-4">Student Profile Analytics Dashboard</h2>
        
        <ul class="nav nav-analytics">
            <li class="nav-item">
                <a class="nav-link" href="view_personal_info_analytics.php">Personal Information</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_family_background_analytics.php">Family Background</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_educational_career_analytics.php">Educational Career</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="view_medical_history_analytics.php">Medical History</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="other_problems_analytics.php">Other Student Problems</a>
            </li>
        </ul>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Self-reported Problems</h5>
                        <div class="chart-container">
                            <canvas id="selfProblemsChart"></canvas>
                        </div>
                        <div id="selfProblemsLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Family Member Problems</h5>
                        <div class="chart-container">
                            <canvas id="familyProblemsChart"></canvas>
                        </div>
                        <div id="familyProblemsLegend" class="chart-legend"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const problemsData = <?php echo json_encode($problems_data); ?>;
        
        const colors = {
            'Alcohol/Substance Abuse': '#4299E1',
            'Eating Disorder': '#48BB78',
            'Depression': '#ED64A6',
            'Aggression': '#ECC94B',
            'Others': '#9F7AEA',
            'NO PROBLEMS': '#667EEA'
        };

        function createChart(elementId, data, title) {
        if (!data || data.length === 0) {
            console.warn(`No data available for ${title}`);
            const container = document.getElementById(elementId).closest('.card-body');
            container.innerHTML = `
                <h5 class="card-title">${title}</h5>
                <div class="alert alert-info">No data available for ${title}</div>
            `;
            return null;
        }

            const ctx = document.getElementById(elementId);
            const legendContainer = document.getElementById(`${elementId}Legend`);
            
            // Prepare data
            const labels = data.map(item => item.problem);
            const values = data.map(item => parseInt(item.count));
            const backgroundColor = labels.map(label => {
                if (label.startsWith('Others:')) return colors['Others'];
                return colors[label] || '#CBD5E0';
            });
            
            const total = values.reduce((a, b) => a + b, 0);

            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: backgroundColor,
                        borderColor: backgroundColor,
                        borderWidth: 1,
                        borderRadius: 4,
                        maxBarThickness: 35
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.raw;
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `Count: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: {
                                display: true
                            }
                        },
                        y: {
                            grid: {
                                display: false
                            }
                        }
                    }
                },
                plugins: [{
                    id: 'customCanvasBackgroundColor',
                    beforeDraw: (chart) => {
                        const ctx = chart.canvas.getContext('2d');
                        ctx.save();
                        ctx.globalCompositeOperation = 'destination-over';
                        ctx.fillStyle = 'white';
                        ctx.fillRect(0, 0, chart.width, chart.height);
                        ctx.restore();
                    }
                }]
            });

            // Create custom legend
            legendContainer.innerHTML = '';
            labels.forEach((label, index) => {
                const count = values[index];
                const percentage = ((count / total) * 100).toFixed(1);
                
                const legendItem = document.createElement('div');
                legendItem.className = 'legend-item';
                legendItem.innerHTML = `
                    <div class="legend-color" style="background-color: ${backgroundColor[index]};"></div>
                    <span class="legend-label">${label}</span>
                    <span class="legend-value">(${percentage}%)</span>
                `;
                
                legendContainer.appendChild(legendItem);
            });

            // Add context menu for export
            ctx.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                
                Swal.fire({
                    title: 'Export Data',
                    text: 'Do you want to export this data as CSV?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Export',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        exportToCSV(chart, title);
                    }
                });
            });

            return chart;
        }

        function exportToCSV(chart, title) {
            const labels = chart.data.labels;
            const values = chart.data.datasets[0].data;
            const total = values.reduce((a, b) => a + b, 0);
            
            const timestamp = new Date().toISOString().split('T')[0];
            const filename = `${title.toLowerCase().replace(/\s+/g, '_')}_${timestamp}.csv`;

            const formattedRows = labels.map((label, index) => {
                const value = values[index];
                const percentage = ((value / total) * 100).toFixed(2);
                
                const formattedLabel = label.replace(/[–—]/g, '-');
                
                return [
                    formattedLabel,
                    value.toString(),
                    percentage + '%'
                ];
            });

            const rows = [
                ['Problem Category', 'Count', 'Percentage'],
                ...formattedRows,
                [], // Empty row for spacing
                ['Total', total.toString(), '100%'],
                [], // Empty row for spacing
                ['Report Generated:', new Date().toLocaleString()]
            ];

            // Convert rows to CSV format
            const csvContent = rows.map(row => 
                row.map(cell => {
                    if (cell && (cell.includes(',') || cell.includes('"') || cell.includes('\n'))) {
                        return `"${cell.replace(/"/g, '""')}"`;
                    }
                    return cell;
                }).join(',')
            ).join('\n');

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            Swal.fire({
                icon: 'success',
                title: 'Export Successful',
                text: `Data has been exported to ${filename}`,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            });
        }

        // Create both charts when document is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (problemsData.self.length > 0) {
                createChart('selfProblemsChart', problemsData.self, 'Self-reported Problems');
            }
            
            if (problemsData.family.length > 0) {
                createChart('familyProblemsChart', problemsData.family, 'Family Member Problems');
            }

            // Handle responsive behavior
            window.addEventListener('resize', function() {
                if (problemsData.self.length > 0) {
                    createChart('selfProblemsChart', problemsData.self, 'Self-reported Problems');
                }
                if (problemsData.family.length > 0) {
                    createChart('familyProblemsChart', problemsData.family, 'Family Member Problems');
                }
            });
        });
    </script>
</body>
</html>