<?php
// Set page title
$page_title = "Consolidated DPCR Report - EPMS";

// Start session
session_start();

// Check if user is logged in and is president
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'president') {
    header("Location: access_denied.php");
    exit();
}

// Database connection
require_once 'includes/db_connect.php';

// Get filter parameters from the URL
$filter_period = isset($_GET['period']) && $_GET['period'] !== 'all' ? $_GET['period'] : null;
$filter_department = isset($_GET['department']) && $_GET['department'] !== 'all' ? $_GET['department'] : null;
$filter_status = isset($_GET['document_status']) && $_GET['document_status'] !== 'all' ? $_GET['document_status'] : null;


// Build the query
$query = "SELECT
            r.id,
            d.name AS department_name,
            u.name AS head_name,
            r.period,
            r.document_status,
            AVG(de.a_rating) AS average_rating
          FROM
            records r
          JOIN
            users u ON r.user_id = u.id
          LEFT JOIN
            departments d ON u.department_id = d.id
          LEFT JOIN
            dpcr_entries de ON r.id = de.record_id
          WHERE
            r.form_type = 'DPCR'
            AND u.role = 'department_head'";

$params = [];
$types = "";

if ($filter_period) {
    $query .= " AND r.period = ?";
    $params[] = $filter_period;
    $types .= "s";
}

if ($filter_department) {
    $query .= " AND d.id = ?";
    $params[] = $filter_department;
    $types .= "i";
}

if ($filter_status) {
    $query .= " AND r.document_status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$query .= " GROUP BY r.id, d.name, u.name, r.period, r.document_status ORDER BY d.name, r.period;";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records_result = $stmt->get_result();

function getRatingInterpretation($rating) {
    if ($rating === null) return 'N/A';
    if ($rating >= 4.5) return 'Outstanding';
    if ($rating >= 3.5) return 'Very Satisfactory';
    if ($rating >= 2.5) return 'Satisfactory';
    if ($rating >= 1.5) return 'Unsatisfactory';
    return 'Poor';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #fff;
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .report-header img {
            max-width: 80px;
            height: auto;
        }
        .report-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .filter-info {
            font-size: 0.9rem;
            color: #6c757d;
        }
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .no-print { 
                display: none !important;
            }
            #print-container {
                display: block !important;
                width: 100%;
                margin: 0;
                padding: 0;
            }
            @page {
                size: letter portrait; 
                margin: 0.5in;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="container py-4">
        <div class="text-center mb-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">Print Report</button>
            <a href="all_dpcr.php" class="btn btn-secondary">Back to DPCR List</a>
        </div>
        <div id="print-container">
            <div class="report-header">
                <img src="images/CCA.jpg" alt="City College of Angeles Logo" class="mb-2" onerror="this.onerror=null; this.src='https://placehold.co/80x80/cccccc/333333?text=Logo'">
                <h4>City College of Angeles</h4>
                <p class="report-title">Consolidated DPCR Report</p>
                <p class="filter-info">
                    <strong>Period:</strong> <?php echo htmlspecialchars($filter_period ?? 'All'); ?> | 
                    <strong>Department:</strong> <?php echo htmlspecialchars($filter_department ? $conn->query("SELECT name FROM departments WHERE id = $filter_department")->fetch_assoc()['name'] : 'All'); ?> | 
                    <strong>Status:</strong> <?php echo htmlspecialchars($filter_status ?? 'All'); ?><br>
                    <strong>Printed on:</strong> <?php echo date('F d, Y'); ?>
                </p>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th>Department</th>
                            <th>Department Head</th>
                            <th>Period</th>
                            <th>Status</th>
                            <th>Average Rating</th>
                            <th>Rating Interpretation</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($records_result->num_rows > 0): ?>
                            <?php while ($row = $records_result->fetch_assoc()): ?>
                                <tr class="text-center">
                                    <td><?php echo htmlspecialchars($row['department_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['head_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['period']); ?></td>
                                    <td><?php echo htmlspecialchars($row['document_status']); ?></td>
                                    <td><?php echo $row['average_rating'] !== null ? number_format($row['average_rating'], 2) : 'N/A'; ?></td>
                                    <td><?php echo getRatingInterpretation($row['average_rating']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No records found for the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-5">
                <p class="text-center text-muted">*** End of Report ***</p>
            </div>
        </div>
    </div>
</body>
</html>
