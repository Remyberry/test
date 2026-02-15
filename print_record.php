<?php
// Include session management
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: records.php");
    exit();
}

$record_id = intval($_GET['id']);

// Database connection
require_once 'includes/db_connect.php';

// Get user info
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$department_id = $_SESSION['user_department_id'] ?? null;

// Get record data
$record_query = "SELECT r.*, u.name as employee_name, u.department_id, u.position as employee_position,
                 d.name as department_name, dh.name as reviewer_name
                 FROM records r 
                 JOIN users u ON r.user_id = u.id 
                 LEFT JOIN departments d ON u.department_id = d.id
                 LEFT JOIN users dh ON r.created_by = dh.id
                 WHERE r.id = ?";
$stmt = $conn->prepare($record_query);
$stmt->bind_param("i", $record_id);
$stmt->execute();
$record_result = $stmt->get_result();

if ($record_result->num_rows === 0) {
    die("Record not found!");
}

$record = $record_result->fetch_assoc();
$stmt->close();

$dept_head_name = $record['reviewer_name'] ?? "N/A";

$form_type = $record['form_type'];

// Check permission to view this record
$has_permission = false;

// The record owner can always view
if ($record['user_id'] == $user_id) {
    $has_permission = true;
}
// Department head can view records from their department
else if ($user_role == 'department_head' && $record['department_id'] == $department_id) {
    $has_permission = true;
}
// Admin and president can view all records
else if ($user_role == 'admin' || $user_role == 'president') {
    $has_permission = true;
}

if (!$has_permission) {
    die("You don't have permission to view this record!");
}

function getPDSValue($data, $key, $default = '') {
    return htmlspecialchars($data[$key] ?? $default);
}
function getPDSCheck($data, $key, $value) {
    return ($data[$key] ?? '') === $value ? '☑' : '☐';
}


// Get entries based on form type
$entries = [];

if ($record['form_type'] === 'DPCR') {
    $content = json_decode($record['content'], true);
    $strategic_functions = [];
    $core_functions = [];
    $support_functions = [];

    if ($content !== null && is_array($content)) {
        // Check for new vs. old data structure. New structure has 'indicators'.
        if (isset($content['strategic_functions'][0]['indicators']) || isset($content['core_functions'][0]['indicators']) || isset($content['support_functions'][0]['indicators'])) {
            // New nested structure
            $strategic_functions = $content['strategic_functions'] ?? [];
            $core_functions = $content['core_functions'] ?? [];
            $support_functions = $content['support_functions'] ?? [];
        } else if (!empty($content['strategic_functions']) || !empty($content['core_functions']) || !empty($content['support_functions'])) {
            // Old flat structure, transform it for viewing
            $transform_legacy = function($entries) {
                if (empty($entries)) return [];
                $grouped = [];
                // Group by major_output as it was repeated in the old format
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
    
    $entries = [
        'strategic' => $strategic_functions,
        'core' => $core_functions,
        'support' => $support_functions,
    ];
} else if ($record['form_type'] === 'IPCR') {
    $content = json_decode($record['content'], true);

    if ($content !== null && is_array($content)) {
        // Extract the entries directly from the decoded content
        $strategic_entries = $content['strategic_functions'] ?? [];
        $core_entries = $content['core_functions'] ?? [];
        $support_entries = $content['support_functions'] ?? [];
    } else {
        // Handle case where JSON decoding failed or 'content' is malformed
        $strategic_entries = [];
        $core_entries = [];
        $support_entries = [];
        // Optionally add error logging here
    }
    
    $entries = [
        'strategic' => $strategic_entries,
        'core' => $core_entries,
        'support' => $support_entries
    ];
} else if ($record['form_type'] === 'IDP') {
    $content = json_decode($record['content'], true);
    $idp_goals = $content['idp_goals'] ?? [];
}
$is_dept_head_ipcr_view = ($record['form_type'] === 'IPCR' && $user_role === 'department_head');
// $title = $record['form_type'] === 'PDS' ? 'PERSONAL DATA SHEET' : htmlspecialchars($record['form_type']) . ' Record';
// $page_title = $title . ' - ' . htmlspecialchars($record['employee_name']);
// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print <?php echo $record['form_type']; ?> - <?php echo htmlspecialchars($record['employee_name']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #fff;
        }
        .print-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .print-header img {
            max-width: 80px;
            height: auto;
        }
        
        .section-title {
            background-color: #f0f0f0;
            padding: 10px;
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        table.data-table th,
        table.data-table td {
            border: 1px solid #ddd; /* Lighter border for general data tables */
            padding: 8px;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            text-align: left;
        }
        
        /* IDP Specific Styles */
        .idp-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .idp-header h1 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }
        .idp-header h2 {
            margin: 5px 0;
            font-size: 14px;
            font-weight: bold;
            color: #000;
        }
        .idp-section {
            margin-bottom: 20px;
        }
        .idp-info-row {
            margin: 10px 0;
        }
        table.idp-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.idp-table td {
            border: 1px solid black;
            padding: 10px;
            vertical-align: top;
        }
        .idp-signature-section {
            margin-top: 80px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        <?php if ($form_type === 'PDS'): ?>
        
        .pds-page {
            page-break-after: always; /* Force a new page after each PDS section */
            box-sizing: border-box;
            padding: 0;
            width: 100%;
            border-collapse: collapse;
        }

        .pds-page:last-child {
            page-break-after: avoid; /* Don't break after the last page */
        }
        
        .pds-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            table-layout: fixed; /* Helps in precise column sizing */
        }

        .pds-table td, .pds-table th {
            border: 1px solid #000;
            padding: 1px 3px;
            vertical-align: top;
            line-height: 1.2;
            height: 12px; /* Standard row height for compact form */
        }
        
        .pds-table th {
            text-align: left;
            font-weight: bold;
            background-color: #e0e0e0;
            padding: 2px 3px;
        }

        .pds-header {
            text-align: right;
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .pds-title {
            text-align: center;
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .section-header {
            font-weight: bold;
            background-color: #ccc;
            text-align: center;
            padding: 3px;
            height: 15px !important;
        }
        
        .field-label {
            font-style: italic;
            vertical-align: middle !important;
            font-size: 7pt;
            padding: 0 3px !important;
            border-right: none !important;
            width: 30%; /* Standardize label width */
        }
        
        .field-data {
            vertical-align: middle !important;
            font-weight: bold;
            border-left: 1px solid #000 !important; /* Always ensure separation */
            width: 70%;
            padding: 0 3px !important;
        }
        
        .data-only {
            font-weight: bold;
            padding: 0 3px !important;
        }

        .centered-data {
            text-align: center;
        }

        .small-text {
            font-size: 6pt;
            text-align: center;
            padding: 0 !important;
            height: 6px;
        }
        
        /* For the long lists like Work Experience or L&D */
        .multi-row-table td {
            height: 12px;
        }
        
        /* Watermark and Page Number for all PDS pages */
        .pds-page-number {
            position: absolute;
            bottom: 10mm;
            right: 10mm;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .watermark {
            color: #d0d0d0;
            font-size: 15pt;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            white-space: nowrap;
            opacity: 0.5;
            pointer-events: none;
        }

        /* Adjustments for screen view */
        @media screen {
            .pds-page { border: 1px dashed #ccc; margin-bottom: 10px; }
        }
        
        /* Reset table styles for nested tables where full border is not needed */
        .pds-table table {
            border: none;
            table-layout: auto;
        }
        .pds-table table td, .pds-table table th {
            border: none;
        }
        <?php else: ?>
        /* --- IDP & DPCR SPECIFIC STYLES --- */
        
        .header-info {
            margin-bottom: 15px;
            border: 1px solid #000;
            padding: 10px;
        }
        
        /* Generic table styles were removed to avoid conflicts. */
        /* Each form (DPCR, IDP, IPCR) should rely on its own classes or inline styles. */

        .signatures {
            display: flex;
            justify-content: space-around;
            margin-top: 50px;
            page-break-before: avoid;
        }

        .signature-block {
            width: 45%;
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .footnote {
            margin-top: 50px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            font-size: 9pt;
            text-align: center;
            page-break-before: avoid;
        }
        <?php endif; ?>
        
        /* Print-specific CSS */
        @media print {
            body {
                padding: 0;
                /* Reset margin to 0 for print, let @page handle it */
                margin: 0; 
                background-color: #fff;
            }
            
            /* Hide everything not in the print-container on print */
            .no-print, 
            .sidebar, 
            body > *:not(#print-container) { 
                display: none !important;
            }
            
            #print-container {
                display: block !important;
                position: relative; /* Changed from absolute for better flow */
                width: 100%;
                margin: 0; /* Let @page handle margins */
                padding: 0;
                box-sizing: border-box;
            }
            
            /* Ensure the main table and its rows can break across pages */
            .data-table, .dpcr-data-table, .pds-table, .form-table {
                page-break-inside: auto;
            }
            .data-table tr, .dpcr-data-table tr, .pds-table tr, .form-table tr {
                page-break-inside: avoid; /* Keep rows together */
                page-break-after: auto;
            }
            
            /* Keep header and footer sections together */
            .ipcr-header, .ipcr-footer-section, .dpcr-header-table, .signatures, .footnote {
                page-break-inside: avoid;
            }

            @page {
                size: <?php echo ($form_type === 'IDP' ? 'letter portrait' : (($form_type === 'IPCR' || $form_type === 'DPCR') ? 'legal landscape' : 'letter landscape')); ?>; 
                margin: 0.5in;
            }
            
            /* Header Styling for IDP */
            .header-info {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 5px;
                border-bottom: 2px solid #333; /* Separator */
            }
            .header-info img {
                width: 70px; 
                height: auto;
                margin-right: 20px;
            }
            .header-text {
                text-align: center;
                flex-grow: 1;
            }
            .header-text h4, .header-text h3 {
                margin: 0;
                line-height: 1.2;
            }
            .header-text h4 { font-size: 12pt; }
            .header-text h3 { font-size: 16pt; margin-top: 5px; }

            /* Employee Details Box for IDP */
            .employee-details {
                margin-bottom: 20px;
                border: 1px solid #000;
                padding: 10px;
            }
            .employee-details p {
                margin-bottom: 5px;
                margin-top: 0;
            }
            
            /* Table Styling for IDP form-table */
            .table-bordered {
                border-collapse: collapse;
                width: 100%;
            }
            .table-bordered th, .table-bordered td {
                border: 1px solid #000 !important;
                padding: 6px;
                vertical-align: top;
            }
            .form-table thead th {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
                font-weight: bold;
                text-align: center;
                height: 30px;
            }
            .form-table tbody td {
                min-height: 50px; /* Ensure minimum row height */
            }

            /* Signature Block for IDP */
            .signature-block {
                display: flex;
                justify-content: space-around;
                margin-top: 50px;
                text-align: center;
                width: 100%;
            }
            .signature-item {
                width: 45%;
            }
            .signature-line {
                margin-top: 50px;
                border-bottom: 1px solid #000;
                height: 1px;
                width: 100%;
            }
            .signature-label {
                margin-top: 5px;
                font-size: 9pt;
            }
        }
        
        /* Controls visibility on screen vs print */
        .print-only {
            display: none;
        }
        
        @media print {
            .print-only {
                display: block;
            }
            
            .screen-only {
                display: none;
            }
        }

        /* DPCR Layout Specific Styles */
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
    </style>
    <?php if ($form_type === 'IPCR'): ?>
    <style>
        /* IPCR Specific Styles from Template */
        p.MsoNormal, li.MsoNormal, div.MsoNormal {
            margin-top: 0in;
            margin-right: 0in;
            margin-bottom: 8.0pt;
            margin-left: 0in;
            line-height: 107%;
            font-size: 11.0pt;
            font-family: "Calibri", sans-serif;
        }
        .ipcr-table {
            border-collapse: collapse;
            border: none;
            width: 100%;
        }
        .ipcr-table td {
            padding: 0in 5.4pt;
        }
    </style>
    <?php endif; ?>
    

</head>
<body>
    <!-- Screen-only controls -->
    <div class="screen-only no-print mb-3">
        <button class="btn btn-primary" onclick="window.print()">Print</button>
        <a href="view_record.php?id=<?php echo $record_id; ?>" class="btn btn-secondary">Back</a>
    </div>
    
    <!-- Print container that will be the only thing visible when printing -->
    <div id="print-container">
        
        <!-- DPCR Form Content -->
        <?php if ($record['form_type'] === 'DPCR'): ?>
            <div style="text-align: center; margin-bottom: 10px;">
                <h3 class="dpcr-title">DEPARTMENT PERFORMANCE COMMITMENT AND REVIEW (DPCR)</h3>
                <div class="dpcr-period">
                    I, <strong><?php echo htmlspecialchars($record['employee_name']); ?></strong>, <strong><?php echo htmlspecialchars($record['employee_position']); ?></strong>, commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period
                    <span style="border-bottom: 1px solid #000; padding: 0 50px;">
                        <?php 
                            // Attempt to format the period from the database
                            // Assuming $record['period'] is like 'YYYY-MM-DD to YYYY-MM-DD' or similar
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
                                // Placeholder for City Mayor/Head of Agency Name
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
                                // Placeholder for Date
                                echo date('F d, Y', strtotime($record['date_submitted'] ?? $record['created_at'] ?? 'now'));
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
                        <th class="col-q">Q<sup>2</sup></th>
                        <th class="col-q">T<sup>3</sup></th>
                        <th class="col-q">A<sup>4</sup></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
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

                    $computation_type = $record['computation_type'] ?? 'Type1';
                    $strategic_weight = ($computation_type === 'Type2') ? '(45%)' : '(45%)';
                    $core_weight = ($computation_type === 'Type2') ? '(45%)' : '(55%)';

                    $render_section($entries['strategic'] ?? [], 'I. Strategic Functions', $strategic_weight);
                    $render_section($entries['core'] ?? [], 'II. Core Functions', $core_weight);
                    
                    if ($computation_type === 'Type2' && !empty($entries['support'])) {
                        $render_section($entries['support'], 'III. Support Functions', '(10%)');
                    }

                    if (empty($entries['strategic']) && empty($entries['core'])) {
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
                                // Placeholder for City Mayor/Head of Agency Name
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
                                // Placeholder for City Mayor/Head of Agency Name
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
                                // Placeholder for City Mayor/Head of Agency Name
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
                                // Placeholder for Date
                                echo '____________________'; 
                            ?>
                        </div>
                    </td>
                </tr>
                
            </table>
            <small>Legends :  1 – QUANTITY 		2 – EFFICIENCY		3 – TIMELINESS		4 - AVERAGE</small>
        <?php endif; ?>
        
        <!-- IPCR Form Content -->
        <?php if ($record['form_type'] === 'IPCR'): 
            $content = json_decode($record['content'], true) ?? [];
            $strategic_functions = $content['strategic_functions'] ?? [];
            $core_functions = $content['core_functions'] ?? [];
            $support_functions = $content['support_functions'] ?? [];
        ?>
            
            <div class="WordSection1">
                <p class="MsoNormal align-center" style='text-align:center'>
                    <b>
                    <span style='font-size:12.0pt;line-height:107%;font-family:"Arial",sans-serif'>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR) <br> SELF RATING FORM</span>
                    </b>
                </p>

                <table class="2 border=1 cellspacing=0 cellpadding=0 width=1151" style='border-collapse:collapse;border:none'>
                    <tr style='height:13.3pt'>
                    <td width=170 valign=top style='width:127.35pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:13.3pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>Name of Employee:</span>
                        </p>
                    </td>
                    <td width=295 valign=top style='width:220.9pt;border:none;border-bottom:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:13.3pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <b>
                            <span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars($record['employee_name']); ?></span>
                        </b>
                        </p>
                    </td>
                    <td width=79 valign=top style='width:58.9pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:13.3pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>Position:</span>
                        </p>
                    </td>
                    <td width=242 valign=top style='width:181.65pt;border:none;border-bottom: solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:13.3pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <b>
                            <span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars($record['employee_position'] ?? 'N/A'); ?></span>
                        </b>
                        </p>
                    </td>
                    <td width=59 valign=top style='width:44.15pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:13.3pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>Office:</span>
                        </p>
                    </td>
                    <td width=307 valign=top style='width:230.45pt;border:none;border-bottom: solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:13.3pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <b>
                            <span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars(string: $record['department_name'] ?? 'N/A'); ?></span>
                        </b>
                        </p>
                    </td>
                    </tr>
                    <tr style='height:12.5pt'>
                    <td width=170 valign=top style='width:127.35pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:12.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>Immediate Supervisor:</span>
                        </p>
                    </td>
                    <td width=295 valign=top style='width:220.9pt;border:none;border-bottom:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:12.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <b>
                            <span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars($dept_head_name ?? '____________________'); ?></span>
                        </b>
                        </p>
                    </td>
                    <td width=79 valign=top style='width:58.9pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:12.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>&nbsp;</span>
                        </p>
                    </td>
                    <td width=242 valign=top style='width:181.65pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:12.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>&nbsp;</span>
                        </p>
                    </td>
                    <td width=59 valign=top style='width:44.15pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:12.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>&nbsp;</span>
                        </p>
                    </td>
                    <td width=307 valign=top style='width:230.45pt;border:none;padding:0in 5.4pt 0in 5.4pt; height:12.5pt'>
                        <p class=MsoNormal style='margin-bottom:0in;line-height:normal'>
                        <span style='font-family:"Arial",sans-serif'>&nbsp;</span>
                        </p>
                    </td>
                    </tr>
                </table>
                <br>
            
                <table class="ipcr-table" border="1" cellspacing="0" cellpadding="0" style='margin-left:-.25pt;border-collapse:collapse;border:none'>
                    <tbody>
                        <tr style='height:38.6pt'>
                            <td width=195 valign=top style='width:146.55pt;border:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:38.6pt'>
                                <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'>Output</span></b></p>
                            </td>
                            <td width=223 valign=top style='width:167.05pt;border:solid black 1.0pt; border-left:none;padding:0in 5.4pt 0in 5.4pt;height:38.6pt'>
                                <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'>Success Indicator (Target + Measure)</span></b></p>
                            </td>
                            <td width=239 valign=top style='width:179.6pt;border:solid black 1.0pt; border-left:none;padding:0in 5.4pt 0in 5.4pt;height:38.6pt'>
                                <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'>Actual Accomplishments</span></b></p>
                            </td>
                            <td width=177 colspan=4 valign=top style='width:132.7pt;border:solid black 1.0pt; border-left:none;padding:0in 5.4pt 0in 5.4pt;height:38.6pt'>
                                <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'>Self-Rating</span></b></p>
                            </td>
                            <td width=173 colspan=4 valign=top style='width:129.85pt;border:solid black 1.0pt; border-left:none;padding:0in 5.4pt 0in 5.4pt;height:38.6pt'>
                                <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'>Supervisor's Rating</span></b></p>
                                <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'>&nbsp;</span></b></p>
                            </td>
                            <td width=144 valign=top style='width:1.5in;border:solid black 1.0pt; border-left:none;padding:0in 5.4pt 0in 5.4pt;height:38.6pt'>
                                <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'>Remarks</span></b></p>
                            </td>
                        </tr>
                        <tr style='height:12.85pt'>
                            <td width=195 valign=top style='width:146.55pt;border:solid black 1.0pt; border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'></td>
                            <td width=223 valign=top style='width:167.05pt;border-top:none;border-left: none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:12.85pt'></td>
                            <td width=239 valign=top style='width:179.6pt;border-top:none;border-left: none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:12.85pt'></td>
                            <td width=43 style='width:32.25pt;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>Q<sup>1</sup></span></p></td>
                            <td width=43 style='width:32.25pt;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>E<sup>2</sup></span></p></td>
                            <td width=43 style='width:32.25pt;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>T<sup>3</sup></span></p></td>
                            <td width=48 style='width:35.95pt;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>A<sup>4</sup></span></p></td>
                            <td width=43 style='width:32.25pt;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>Q<sup>1</sup></span></p></td>
                            <td width=43 style='width:32.25pt;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>E<sup>2</sup></span></p></td>
                            <td width=43 style='width:32.25pt;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>T<sup>3</sup></span></p></td>
                            <td width=44 style='width:33.1pt;border-top:none;border-left:none;border-bottom: solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>A<sup>4</sup></span></p></td>
                            <td width=144 valign=top style='width:1.5in;border-top:none;border-left:none; border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt; height:12.85pt'></td>
                        </tr>

                    <?php 
                    function render_ipcr_rows($entries, $section_title) {
                        // Section Header Row
                        echo "<tr style='height:12.85pt'>";
                        echo "<td width=195 valign=top style='width:146.55pt;border:solid black 1.0pt; border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>" . htmlspecialchars($section_title) . "</span></b></p></td>";
                        // Empty cells for the rest of the row to maintain border structure
                        echo "<td width=223 valign=top style='width:167.05pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'></td>";
                        echo "<td width=239 valign=top style='width:179.6pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'></td>";
                        for($i=0; $i<8; $i++) { // 8 rating cells
                             $width = ($i==3 || $i==7) ? 48 : 43; // A column is slightly wider in template
                             $pt_width = ($i==3 || $i==7) ? '35.95pt' : '32.25pt';
                             echo "<td width=$width valign=top style='width:$pt_width;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'></td>";
                        }
                        echo "<td width=144 valign=top style='width:1.5in;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'></td>";
                        echo "</tr>";

                        if (empty($entries)) {
                            // Optional: Render an empty row or message if needed, but template implies structure
                            return;
                        }
                        
                        foreach ($entries as $entry) {
                            echo "<tr style='height:12.85pt'>";
                            // MFO
                            echo "<td width=195 valign=top style='width:146.55pt;border:solid black 1.0pt;border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:\"Arial\",sans-serif'>" . nl2br(htmlspecialchars($entry['mfo'])) . "</span></p></td>";
                            // Indicators
                            echo "<td width=223 valign=top style='width:167.05pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:\"Arial\",sans-serif'>" . nl2br(htmlspecialchars($entry['success_indicators'])) . "</span></p></td>";
                            // Accomplishments
                            echo "<td width=239 valign=top style='width:179.6pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:\"Arial\",sans-serif'>" . nl2br(htmlspecialchars($entry['accomplishments'] ?? '')) . "</span></p></td>";
                            
                            // Ratings Helper
                            $render_rating = function($val, $is_avg=false) {
                                $width = $is_avg ? 48 : 43;
                                $pt_width = $is_avg ? '35.95pt' : '32.25pt';
                                echo "<td width=$width valign=top style='width:$pt_width;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><span style='font-family:\"Arial\",sans-serif'>" . htmlspecialchars($val ?? '') . "</span></p></td>";
                            };

                            $render_rating($entry['q'] ?? '');
                            $render_rating($entry['e'] ?? '');
                            $render_rating($entry['t'] ?? '');
                            $render_rating($entry['a'] ?? '', true);
                            
                            $render_rating($entry['supervisor_q'] ?? '');
                            $render_rating($entry['supervisor_e'] ?? '');
                            $render_rating($entry['supervisor_t'] ?? '');
                            $render_rating($entry['supervisor_a'] ?? '', true);

                            // Remarks
                            echo "<td width=144 valign=top style='width:1.5in;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:\"Arial\",sans-serif'>" . nl2br(htmlspecialchars($entry['remarks'] ?? '')) . "</span></p></td>";
                            echo "</tr>";
                        }
                    }

                    render_ipcr_rows($strategic_functions, 'STRATEGIC FUNCTIONS');
                    render_ipcr_rows($core_functions, 'CORE FUNCTIONS');
                    render_ipcr_rows($support_functions, 'SUPPORT FUNCTIONS');
                    
                    // Summary Row Helper
                    function render_summary_row($title, $self_avg, $sup_avg) {
                        echo "<tr style='height:12.85pt'>";
                        echo "<td width=195 valign=top style='width:146.55pt;border:solid black 1.0pt;border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif;color:black'>$title</span></b></p></td>";
                        // Empty cells
                        echo "<td width=223 valign=top style='width:167.05pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif;color:black'>&nbsp;</span></b></p></td>";
                        echo "<td width=239 valign=top style='width:179.6pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif;color:black'>&nbsp;</span></b></p></td>";
                        
                        // Self Ratings (Empty except Average)
                        for($i=0; $i<3; $i++) echo "<td width=43 valign=bottom style='width:32.25pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>&nbsp;</span></b></p></td>";
                        echo "<td width=48 valign=bottom style='width:35.95pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><span style='font-family:\"Arial\",sans-serif'>" . htmlspecialchars($self_avg) . "</span></p></td>";
                        
                        // Sup Ratings (Empty except Average)
                        for($i=0; $i<3; $i++) echo "<td width=43 valign=bottom style='width:32.25pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>&nbsp;</span></b></p></td>";
                        echo "<td width=44 valign=bottom style='width:33.1pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><span style='font-family:\"Arial\",sans-serif;color:black'>" . htmlspecialchars($sup_avg) . "</span></p></td>";
                        
                        echo "<td width=144 valign=top style='width:1.5in;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>&nbsp;</span></b></p></td>";
                        echo "</tr>";
                    }

                    render_summary_row('Total Strategic Function', $content['summary']['strategic_average'] ?? '', $content['supervisor_summary']['strategic_average'] ?? '');
                    render_summary_row('Total Core Function', $content['summary']['core_average'] ?? '', $content['supervisor_summary']['core_average'] ?? '');
                    render_summary_row('Total Support Function', $content['summary']['support_average'] ?? '', $content['supervisor_summary']['support_average'] ?? '');
                    
                    // Final Rating Row
                    echo "<tr style='height:12.85pt'>";
                    echo "<td width=195 valign=top style='width:146.55pt;border:solid black 1.0pt;border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif;color:black'>Final Average Rating</span></b></p></td>";
                    echo "<td width=223 valign=top style='width:167.05pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif;color:black'>&nbsp;</span></b></p></td>";
                    echo "<td width=239 valign=top style='width:179.6pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif;color:black'>&nbsp;</span></b></p></td>";
                    // Empty Self
                    for($i=0; $i<3; $i++) echo "<td width=43 valign=bottom style='width:32.25pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>&nbsp;</span></b></p></td>";
                    echo "<td width=44 valign=bottom style='width:33.1pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>" . htmlspecialchars($content['summary']['final_rating'] ?? '') . "</span></b></p></td>";
                    // Empty Sup Q, E, T
                    for($i=0; $i<3; $i++) echo "<td width=43 valign=bottom style='width:32.25pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>&nbsp;</span></b></p></td>";
                    // Sup Final
                    echo "<td width=44 valign=bottom style='width:33.1pt;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal align=center style='margin-bottom:0in;text-align:center;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>" . htmlspecialchars($content['supervisor_summary']['final_rating'] ?? '') . "</span></b></p></td>";
                    // Adjectival Rating in Remarks
                    echo "<td width=144 valign=top style='width:1.5in;border-top:none;border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'><p class=MsoNormal style='margin-bottom:0in;line-height:normal'><b><span style='font-family:\"Arial\",sans-serif'>" . htmlspecialchars($content['supervisor_summary']['adjectival_rating'] ?? '') . "</span></b></p></td>";
                    echo "</tr>";
                    ?>
                    
                    <!-- Comments Section -->
                    <tr style='height:12.85pt'>
                        <td width=1152 colspan=12 valign=top style='width:863.75pt;border:solid black 1.0pt; border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>Comments and Recommendations for Development Purposes</span></p>
                        </td>
                    </tr>
                    <tr style='height:12.85pt'>
                        <td width=1152 colspan=12 valign=top style='width:863.75pt;border:solid black 1.0pt; border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'><?php echo nl2br(htmlspecialchars($content['dh_comments'] ?? '')); ?></span></p>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>&nbsp;</span></p>
                        </td>
                    </tr>
                    
                    <!-- Signatures Section -->
                    <tr style='height:88.55pt'>
                        <td width=418 colspan=2 valign=top style='width:313.6pt;border:solid black 1.0pt; border-top:none;padding:0in 5.4pt 0in 5.4pt;height:88.55pt'>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>I hereby certify that I agree with the ratings of my immediate supervisor.</span></p>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>&nbsp;</span></p>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>&nbsp;</span></p>
                            <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars($record['employee_name']); ?></span></b></p>
                        </td>
                        <td width=239 rowspan=2 valign=top style='width:179.6pt;border-top:none; border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:88.55pt'>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>Date</span></p>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>&nbsp;</span></p>
                            <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars($record['date_approved']); ?></span></b></p>
                        </td>
                        <td width=350 colspan=8 valign=top style='width:262.55pt;border-top:none; border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:88.55pt'>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>Assessed by:</span></p>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'><br> I hereby certify that I discussed my assessment of the performance with the employee.</span></p>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>&nbsp;</span></p>
                            <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars($record['reviewer_name'] ?? '__________________'); ?></span></b></p>
                        </td>
                        <td width=144 rowspan=2 valign=top style='width:1.5in;border-top:none; border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:88.55pt'>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>Date</span></p>
                            <p class=MsoNormal style='margin-bottom:0in;line-height:normal'><span style='font-family:"Arial",sans-serif'>&nbsp;</span></p>
                            <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><b><span style='font-family:"Arial",sans-serif'><?php echo htmlspecialchars($record['date_approved']); ?></span></b></p>
                        </td>
                    </tr>
                    <tr style='height:12.85pt'>
                        <td width=418 colspan=2 valign=top style='width:313.6pt;border:solid black 1.0pt; border-top:none;padding:0in 5.4pt 0in 5.4pt;height:12.85pt'>
                            <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif;color:black'>Employee</span></p>
                        </td>
                        <td width=350 colspan=8 valign=top style='width:262.55pt;border-top:none; border-left:none;border-bottom:solid black 1.0pt;border-right:solid black 1.0pt; padding:0in 5.4pt 0in 5.4pt;height:12.85pt'>
                            <p class=MsoNormal align=center style='margin-bottom:0in;text-align:center; line-height:normal'><span style='font-family:"Arial",sans-serif'>Supervisor</span></p>
                        </td>
                    </tr>
                    </tbody>
                </table>
            
                <p class=MsoNormal><i><span style='font-family:"Arial",sans-serif'>Legend:         1 - Quantity          2 - Efficiency          3 - Timeliness          4 - Average</span></i></p>
            </div>
        <?php endif; ?>
        
        <!-- IDP Form Content -->
        <?php if ($record['form_type'] === 'IDP'): 
            $content = json_decode($record['content'], true);
            $idp_goals = $content['idp_goals'] ?? [];
            ?>
            
            <div class="idp-header">
                <h1>REPUBLIC OF THE PHILIPPINES</h1>
                <h1>CITY GOVERNMENT OF ANGELES</h1>
                <h1>STRATEGIC PERFORMANCE MANAGEMENT SYSTEM (SPMS)</h1>
                <br>
                <h2>INDIVIDUAL DEVELOPMENT PLAN</h2>
                <h2>(TARGET SETTING)</h2>
                <p>For the Period of <strong><?php echo htmlspecialchars($record['period']); ?></strong></p>
            </div>

            <div class="idp-section">
                <div class="idp-info-row"><strong>Name:</strong> <?php echo htmlspecialchars($record['employee_name']); ?></div>
                <div class="idp-info-row"><strong>Position:</strong> <?php echo htmlspecialchars($record['employee_position'] ?? 'N/A'); ?></div>
                <div class="idp-info-row"><strong>Department/Office:</strong> <?php echo htmlspecialchars($record['department_name'] ?? 'N/A'); ?></div>
            </div>
            
            <?php if (empty($idp_goals)): ?>
                <p class="text-center">No development goals found.</p>
            <?php else: ?>
                <?php foreach ($idp_goals as $goal): ?>
                    <table class="idp-table">
                        <tr>
                            <td width="50%">
                                <strong>Main Objective/s:</strong>
                                <p><?php echo nl2br(htmlspecialchars($goal['objective'] ?? '')); ?></p>
                            </td>
                            <td width="50%">
                                <strong>Target Date:</strong>
                                <p><?php echo htmlspecialchars($record['period']); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <strong>Plan of Action:</strong>
                                <p><?php echo nl2br(htmlspecialchars($goal['action_plan'] ?? '')); ?></p>
                            </td>
                        </tr>
                    </table>

                    <table class="idp-table">
                        <tr>
                            <td width="50%">
                                <strong>Status(is the plan accomplished or not):</strong>
                                <p><?php echo htmlspecialchars($goal['status'] ?? 'Not Started'); ?></p>
                            </td>
                            <td width="50%">
                                <strong>Date/Period Accomplished:</strong>
                                <p><?php echo htmlspecialchars($goal['date_accomplished'] ?? ''); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Result/s or Outcome/s for Accomplished Plan:</strong>
                                <p><?php echo nl2br(htmlspecialchars($goal['results'] ?? '')); ?></p>
                            </td>
                            <td>
                                <strong>Comment/s or Remark/s of the Supervisor/Head:</strong>
                            </td>
                        </tr>
                    </table>
                    <br>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="idp-signature-section">
                <div style="text-align: center;">
                    <p>_________________________</p>
                    <p>Employee's Signature</p>
                </div>
                <div style="text-align: center;">
                    <p>_________________________</p>
                    <p>Signature of Supervisor/Head</p>
                </div>
            </div>
        <?php endif; ?>
        
        
        <!-- <div class="footnote">
            <p>This document is generated by the City College of Angeles Employee Performance Management System.</p>
            <p>Printed on <?php echo date('F d, Y'); ?></p>
        </div> -->
    </div>
    
    <script>
        // Automatically focus the print container when page loads
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('print-container').focus();
        });
    </script>
</body>
</html> 