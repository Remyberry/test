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
            r.*,
            d.name AS department_name,
            u.name AS employee_name,
            u.position AS employee_position,
            dh.name AS reviewer_name
          FROM
            records r
          JOIN
            users u ON r.user_id = u.id
          LEFT JOIN
            departments d ON u.department_id = d.id
          LEFT JOIN
            users dh ON r.created_by = dh.id
          WHERE
            r.form_type = 'DPCR'
            AND r.document_status IN ('Submitted', 'Approved', 'Pending')"; // Default filter to show only relevant statuses

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

if ($filter_status && ($filter_status === 'Submitted' || $filter_status === 'Approved' || $filter_status === 'Pending')) {
    // If a specific submitted/approved status is requested, apply it
    $query .= " AND r.document_status = ?";
    $params[] = $filter_status;
    $types .= "s";
} else if ($filter_status && $filter_status !== 'all') {
    // If another status is requested, override the default filter to include only that status
    $query = str_replace("AND r.document_status IN ('Submitted', 'Approved', 'Pending')", "AND r.document_status = ?", $query);
    $params[] = $filter_status;
    $types .= "s";
}

$query .= " ORDER BY d.name, r.date_created DESC;";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records_result = $stmt->get_result();

$dpcr_records_by_department = [];
if ($records_result->num_rows > 0) {
    while ($row = $records_result->fetch_assoc()) {
        $dpcr_records_by_department[$row['department_name']][] = $row;
    }
}

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
        .dpcr-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .dpcr-period {
            font-size: 10pt;
            margin-bottom: 20px;
        }
        .dpcr-header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 10pt;
        }
        .dpcr-header-table td {
            border: 1px solid #000;
            padding: 5px 8px;
            vertical-align: top;
        }
        .dpcr-header-table .signature-cell {
            height: 50px; /* Space for signature */
        }
        .dpcr-header-table .align-center {
            text-align: center;
        }
        .dpcr-rating-key-cell {
            width: 300px;
            padding: 0 !important;
        }
        .dpcr-rating-key-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
        }
        .dpcr-rating-key-table td {
            border: none;
            padding: 1px 3px;
        }
        
        /* DPCR Main Table Styles */
        .dpcr-data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 9pt; /* Smaller font for more compact table */
            table-layout: fixed; /* Ensures column widths are respected */
        }
        .dpcr-data-table th,
        .dpcr-data-table td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: middle; /* Center vertically */
            line-height: 1.2;
        }
        .dpcr-data-table th {
            background-color: #d0d0d0;
            font-weight: bold;
            text-align: center;
        }
        .dpcr-data-table .col-mfo { width: 15%; }
        .dpcr-data-table .col-indicators { width: 25%; }
        .dpcr-data-table .col-budget { width: 10%; }
        .dpcr-data-table .col-accountable { width: 15%; }
        .dpcr-data-table .col-accomplishments { width: 15%; }
        .dpcr-data-table .col-q { width: 5%; text-align: center; } /* Q1, E2, T3, A4 */
        .dpcr-data-table .col-remarks { width: 10%; }
        /* Print-specific CSS */
        @media print {
            .container {
                min-width: 100% !important;
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            @page {
                size: 14in 8.5in;
                margin: 0.5in;
            }
            
            body {
                background-color: #fff;
                font-size: 10px; 
            }
            
            .no-print {
                display: none !important;
            }
            
            .print-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                width: 100%;
                max-width: none !important; /* Add this to guarantee full expansion */
            }
            .dpcr-page-break {
                page-break-after: always;
            }
            .dpcr-page-break:last-child {
                page-break-after: avoid;
            }
        }
    </style>
</head>
<body>
    <?php
    // Define the render_section function needed for DPCR output
    $render_section = function($section_entries, $section_title, $weight) {
        if (empty($section_entries)) {
            return;
        }
    ?>
        <tr>
            <td colspan="10" style="font-weight: bold; background-color: #f0f0f0;"><?php echo htmlspecialchars($section_title) . ' ' . htmlspecialchars($weight); ?></td>
        </tr>
    <?php
        foreach ($section_entries as $mfo_entry) {
            $indicators = $mfo_entry['indicators'] ?? [];
            $indicator_count = count($indicators);
            if ($indicator_count === 0) continue;

            $first_indicator = true;
            foreach ($indicators as $indicator) {
    ?>
                <tr>
                    <?php if ($first_indicator): ?>
                        <td class="col-mfo" rowspan="<?php echo $indicator_count; ?>"><?php echo nl2br(htmlspecialchars($mfo_entry['major_output'])); ?></td>
                    <?php endif; ?>
                    <td class="col-indicators"><?php echo nl2br(htmlspecialchars($indicator['success_indicators'])); ?></td>
                    <td class="col-budget" style="text-align: right;"><?php echo isset($indicator['budget']) && is_numeric($indicator['budget']) ? number_format($indicator['budget'], 2) : 'N/A'; ?></td>
                    <td class="col-accountable"><?php echo htmlspecialchars($indicator['accountable']); ?></td>
                    <td class="col-accomplishments"><?php echo nl2br(htmlspecialchars($indicator['actual_accomplishments'] ?? '')); ?></td>
                    <td class="col-q"><?php echo htmlspecialchars($indicator['q_rating'] ?? ''); ?></td>
                    <td class="col-q"><?php echo htmlspecialchars($indicator['e_rating'] ?? ''); ?></td>
                    <td class="col-q"><?php echo htmlspecialchars($indicator['t_rating'] ?? ''); ?></td>
                    <td class="col-q"><?php echo htmlspecialchars($indicator['a_rating'] ?? ''); ?></td>
                    <td class="col-remarks"><?php echo htmlspecialchars($indicator['remarks'] ?? ''); ?></td>
                </tr>
    <?php
                        $first_indicator = false;
                    }
                }
            };

    // Helper function to get department name
    $get_department_name = function($conn, $id) {
        if (!$id) return 'All';
        $stmt = $conn->prepare("SELECT name FROM departments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['name'] ?? 'Unknown Department';
    };
    ?>
    <div class="container py-4">
        <div class="text-center mb-4 no-print">
            <button class="btn btn-primary" onclick="window.print()">Print Report</button>
            <a href="all_dpcr.php" class="btn btn-secondary">Back to DPCR List</a>
        </div>
        <div id="print-container">
            <div class="report-header" style="text-align: center;">
                <img src="images/CCA.jpg" alt="City College of Angeles Logo" class="mb-2" style="height: 100px; width: auto; object-fit: contain; display: block; margin: 0 auto;" onerror="this.onerror=null; this.src='/images/CCA.jpg';">
                <h4>City College of Angeles</h4>
                <p class="report-title">Consolidated DPCR Report</p>
                <p class="filter-info">
                    <strong>Period:</strong> <?php echo htmlspecialchars($filter_period ?? 'All'); ?> | 
                    <strong>Department:</strong> <?php echo htmlspecialchars($get_department_name($conn, $filter_department)); ?> | 
                    <strong>Status:</strong> <?php echo htmlspecialchars($filter_status ?? 'All'); ?><br>
                    <strong>Printed on:</strong> <?php echo date('F d, Y'); ?>
                </p>
            </div>
            
            <?php if (empty($dpcr_records_by_department)): ?>
                <div class="text-center py-4">No records found for the selected filters.</div>
            <?php else: ?>
                <?php $is_first_dpcr = true; ?>
                <?php foreach ($dpcr_records_by_department as $department_name => $records): ?>
                    <?php if (!$is_first_dpcr): ?>
                        <div class="dpcr-page-break"></div>
                    <?php endif; ?>
                    <h3 class="text-center mb-3 mt-4">Department: <?php echo htmlspecialchars($department_name); ?></h3>
                    <?php $loop_is_first_dpcr_in_department = true; ?>
                    <?php foreach ($records as $record): ?>
                        <?php if (!$loop_is_first_dpcr_in_department): // Add page break between DPCRs, but not between department header and first DPCR in that department ?>
                            <div class="dpcr-page-break"></div>
                        <?php endif; ?>

                        <div style="text-align: center; margin-bottom: 10px;">
                            <h3 class="dpcr-title">DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</h3>
                            <div class="dpcr-period">
                                I, <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong>, <strong><?php echo htmlspecialchars($record['employee_position']); ?></strong>, commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period
                                <span style="border-bottom: 1px solid #000; padding: 0 50px;">
                                    <?php 
                                        $period_parts = explode(' to ', $record['period']);
                                        if (count($period_parts) === 2) {
                                            $start_date = date('F j', strtotime($period_parts[0]));
                                            $end_date = date('F j, Y', strtotime($period_parts[1]));
                                            echo htmlspecialchars($start_date . ' to ' . $end_date);
                                        } else {
                                            echo htmlspecialchars($record['period']);
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <table class="dpcr-header-table">
                            <tr>
                                <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                                    Approved by:
                                    <div style="height: 20px;"></div>
                                    <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                                        <?php 
                                            echo '____________________'; 
                                        ?>
                                    </div>
                                    <small>City Mayor / Head of Agency</small>
                                </td>
                                <td style="width: 50%;" rowspan="2" class="dpcr-rating-key-cell">
                                    <table class="dpcr-rating-key-table">
                                        <tr><td>5 - OUTSTANDING</td></tr>
                                        <tr><td>4 - VERY SATISFACTORY</td></tr>
                                        <tr><td>3 - SATISFACTORY</td></tr>
                                        <tr><td>2 - UNSATISFACTORY</td></tr>
                                        <tr><td>1 - POOR</td></tr>
                                    </table>
                                </td>
                                <td style="width: 25%;" class="align-center signature-cell">
                                    Date
                                    <div style="height: 20px;"></div>
                                    <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                                        <?php 
                                            echo date('F d, Y', strtotime($record['date_submitted'] ?? $record['date_created'] ?? 'now'));
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        
                        <table class="dpcr-data-table">
                            <thead>
                                <tr>
                                    <th class="col-mfo" rowspan="2">MAJOR FINAL OUTPUT/PAP</th>
                                    <th class="col-indicators" rowspan="2">SUCCESS INDICATORS (Targets + Measures)</th>
                                    <th class="col-budget" rowspan="2">ALLOTTED BUDGET</th>
                                    <th class="col-accountable" rowspan="2">DIVISIONS/INDIVIDUALS ACCOUNTABLE</th>
                                    <th class="col-accomplishments" rowspan="2">ACTUAL ACCOMPLISHMENTS</th>
                                    <th colspan="4">RATING</th>
                                    <th class="col-remarks" rowspan="2">REMARKS</th>
                                </tr>
                                <tr>
                                    <th class="col-q">Q<sup>1</sup></th>
                                    <th class="col-q">E<sup>2</sup></th>
                                    <th class="col-q">T<sup>3</sup></th>
                                    <th class="col-q">A<sup>4</sup></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $content = json_decode($record['content'], true);
                                $strategic_functions = [];
                                $core_functions = [];
                                $support_functions = [];
                                
                                if ($content !== null && is_array($content)) {
                                    if (isset($content['strategic_functions'][0]['indicators']) || isset($content['core_functions'][0]['indicators']) || isset($content['support_functions'][0]['indicators'])) {
                                        $strategic_functions = $content['strategic_functions'] ?? [];
                                        $core_functions = $content['core_functions'] ?? [];
                                        $support_functions = $content['support_functions'] ?? [];
                                    } else if (!empty($content['strategic_functions']) || !empty($content['core_functions']) || !empty($content['support_functions'])) {
                                        $transform_legacy = function($entries) {
                                            if (empty($entries)) return [];
                                            $grouped = [];
                                            foreach ($entries as $entry) {
                                                $mfo = $entry['major_output'] ?? 'Uncategorized';
                                                if (!isset($grouped[$mfo])) {
                                                    $grouped[$mfo] = [
                                                        'major_output' => $mfo,
                                                        'indicators' => []
                                                    ];
                                                }
                                                $grouped[$mfo]['indicators'][] = $entry;
                                            }
                                            return array_values($grouped);
                                        };
                                        $strategic_functions = $transform_legacy($content['strategic_functions'] ?? []);
                                        $core_functions = $transform_legacy($content['core_functions'] ?? []);
                                        $support_functions = $transform_legacy($content['support_functions'] ?? []);
                                    }
                                }

                                $computation_type = $record['computation_type'] ?? 'Type1';
                                $strategic_weight = ($computation_type === 'Type2') ? '(45%)' : '(45%)';
                                $core_weight = ($computation_type === 'Type2') ? '(45%)' : '(55%)';

                                $render_section($strategic_functions, 'I. Strategic Functions', $strategic_weight);
                                $render_section($core_functions, 'II. Core Functions', $core_weight);
                                
                                if ($computation_type === 'Type2' && !empty($support_functions)) {
                                    $render_section($support_functions, 'III. Support Functions', '(10%)');
                                }

                                if (empty($strategic_functions) && empty($core_functions)) {
                                    echo '<tr><td colspan="10" style="text-align: center;">No DPCR outputs defined</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>

                        <table class="dpcr-header-table">
                            <tr>
                                <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                                    Assessed by:
                                    <div style="height: 20px;"></div>
                                    <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                                        <?php 
                                            echo '____________________'; 
                                        ?>
                                    </div>
                                    <small>Planning Office</small>
                                </td>
                                <td style="width: 8%;" rowspan="2" class="dpcr-rating-key-cell">
                                    <table class="dpcr-rating-key-table">
                                        <tr><td>Date</td></tr>
                                    </table>
                                </td>
                                <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                                    <div style="height: 40px;"></div>
                                    <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                                        <?php 
                                            echo '____________________'; 
                                        ?>
                                    </div>
                                    <small>PMT</small>
                                </td>
                                <td style="width: 25%;" class="align-center signature-cell" colspan="2">
                                    Final rating by:
                                    <div style="height: 20px;"></div>
                                    <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                                        <?php 
                                            echo '____________________'; 
                                        ?>
                                    </div>
                                    <small>City Mayor</small>
                                </td>
                                <td style="width: 25%;" class="align-center signature-cell">
                                    Date
                                    <div style="height: 20px;"></div>
                                    <div style="border-bottom: 1px solid #000; margin: 0 10px;">
                                        <?php 
                                            echo '____________________'; 
                                        ?>
                                    </div>
                                </td>
                            </tr>
                        </table>
                        <small>Legends :  Q<sup>1</sup> – QUANTITY 		E<sup>2</sup> – EFFICIENCY		T<sup>3</sup> – TIMELINESS		A<sup>4</sup> - AVERAGE</small>
                        <?php $is_first_dpcr = false; $loop_is_first_dpcr_in_department = false; ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
