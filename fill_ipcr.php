<?php
ob_start();
// Set page title
$page_title = "Fill IPCR Form - EPMS";

// Include header
include_once('includes/header.php');
include_once('includes/form_workflow.php');


// Check if user is logged in and has the correct role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['regular_employee', 'department_head'])) {
    header("Location: access_denied.php");
    exit();
}

// Check if record ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: ipcr.php");
    exit();
}

$record_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Database connection
require_once 'includes/db_connect.php';

// Get record details, ensuring it belongs to the current user and has the correct status
$query = "SELECT r.*, u_dh.name as dh_name 
          FROM records r 
          JOIN users u_dh ON r.created_by = u_dh.id
          WHERE r.id = ? AND r.user_id = ? AND r.form_type = 'IPCR' 
          AND (r.document_status = 'Distributed' OR r.document_status = 'For Revision')";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $record_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // If no record is found, either it doesn't exist, doesn't belong to the user, or has a wrong status
    $_SESSION['error_message'] = "The IPCR form you are trying to access is not available or you do not have permission to view it.";
    header("Location: ipcr.php");
    exit();
}

$record = $result->fetch_assoc();
$content = json_decode($record['content'], true);

// Get user and department info
$user_name = $_SESSION['user_name'];
$user_department_id = $_SESSION['user_department_id'];
$dept_query = "SELECT name FROM departments WHERE id = ?";
$dept_stmt = $conn->prepare($dept_query);
$dept_stmt->bind_param("i", $user_department_id);
$dept_stmt->execute();
$dept_result = $dept_stmt->get_result();
$department_name = ($dept_result->num_rows > 0) ? $dept_result->fetch_assoc()['name'] : 'Unknown Department';


// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_ipcr'])) {
    $new_content_json = $_POST['content'];
    $new_content_array = json_decode($new_content_json, true);

    if (json_last_error() === JSON_ERROR_NONE) {
        
        $update_query = "UPDATE records SET content = ?, document_status = 'For Review', date_submitted = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_content_json, $record_id);

        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Your IPCR has been successfully submitted For Review.";
            header("Location: records.php");
            exit();
        } else {
            $error_message = "Error submitting IPCR: " . $conn->error;
        }

    } else {
        $error_message = "There was an error processing the form data.";
    }
}
?>

<!-- Main content for filling IPCR -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Fill Individual Performance Commitment and Review</h1>
        <a href="ipcr.php" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to My IPCRs
        </a>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?php echo $error_message; ?></div>
    <?php endif; ?>

    <form action="fill_ipcr.php?id=<?php echo $record_id; ?>" method="POST" id="fill-ipcr-form">
        <div class="card">
            <div class="card-header bg-light">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-0">IPCR for <?php echo htmlspecialchars($record['period']); ?></h5>
                        <small>Distributed by: <strong><?php echo htmlspecialchars($record['dh_name']); ?></strong></small>
                    </div>
                     <div class="col-md-4 text-end">
                        <strong>Status:</strong>
                        <span class="badge <?php echo $record['document_status'] == 'For Revision' ? 'bg-danger' : 'bg-info text-dark'; ?>">
                            <?php echo htmlspecialchars($record['document_status']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <p>I, <u><?php echo htmlspecialchars($user_name); ?></u>, of <u><?php echo htmlspecialchars($department_name); ?></u>, 
                    commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period <strong><?php echo htmlspecialchars($record['period']); ?></strong>.</p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th rowspan="2" class="align-middle text-center" style="width: 20%">MAJOR FINAL OUTPUT (MFO)</th>
                                <th rowspan="2" class="align-middle text-center" style="width: 20%">SUCCESS INDICATORS</th>
                                <th rowspan="2" class="align-middle text-center" style="width: 15%">ACTUAL ACCOMPLISHMENTS</th>
                                <th colspan="4" class="text-center">SELF-RATING</th>
                                <th rowspan="2" class="align-middle text-center" style="width: 15%">REMARKS</th>
                            </tr>
                            <tr>
                                <th class="text-center">Q</th>
                                <th class="text-center">E</th>
                                <th class="text-center">T</th>
                                <th class="text-center">A</th>
                            </tr>
                        </thead>
                        <tbody id="ipcr-table-body">
                            <!-- JS will populate this -->
                        </tbody>
                    </table>
                </div>

                <!-- Summary -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Rating Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Strategic Functions Average:</strong>
                                <p id="strategic-avg-display" class="fs-4 fw-bold">0.00</p>
                            </div>
                            <div class="col-md-4">
                                <strong>Core Functions Average:</strong>
                                <p id="core-avg-display" class="fs-4 fw-bold">0.00</p>
                            </div>
                            <div class="col-md-4" id="support-summary" style="display: none;">
                                <strong>Support Functions Average:</strong>
                                <p id="support-avg-display" class="fs-4 fw-bold">0.00</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <h4>Final Average Rating:</h4>
                                <p id="final-rating-display" class="display-4 fw-bold text-primary">0.00</p>
                            </div>
                            <div class="col-md-6">
                                <h4>Adjectival Rating:</h4>
                                <p id="adjectival-rating" class="display-6 text-primary">Poor</p>
                            </div>
                        </div>
                    </div>
                </div>

                 <div class="d-flex justify-content-end mt-4">
                    <button type="submit" name="submit_ipcr" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Submit For Review
                    </button>
                </div>
            </div>
        </div>
        <input type="hidden" name="content" id="form-content">
    </form>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="js/auto_scoring.js"></script>

<script>
$(document).ready(function() {
    const content = <?php echo json_encode($content); ?>;
    const computationType = content.computation_type || 'Type1';

    if (computationType === 'Type2') {
        $('#support-summary').show();
    }

    function getRatingInterpretation(averageScore) {
        const score = parseFloat(averageScore);
        
        if (score >= 4.5) return "Outstanding";
        if (score >= 3.5) return "Very Satisfactory";
        if (score >= 2.5) return "Satisfactory";
        if (score >= 1.5) return "Unsatisfactory";
        return "Poor";
    }

    function updateFinalRatingSummary() {
        let strategicTotal = 0;
        let strategicCount = 0;
        $('tr.strategic-function-row').each(function() {
            const avg = parseFloat($(this).find('.average-rating').val());
            if (!isNaN(avg)) {
                strategicTotal += avg;
                strategicCount++;
            }
        });
        const strategicAverage = strategicCount > 0 ? (strategicTotal / strategicCount) : 0;

        let coreTotal = 0;
        let coreCount = 0;
        $('tr.core-function-row').each(function() {
            const avg = parseFloat($(this).find('.average-rating').val());
            if (!isNaN(avg)) {
                coreTotal += avg;
                coreCount++;
            }
        });
        const coreAverage = coreCount > 0 ? (coreTotal / coreCount) : 0;

        let supportAverage = 0;
        if (computationType === 'Type2') {
            let supportTotal = 0;
            let supportCount = 0;
            $('tr.support-function-row').each(function() {
                const avg = parseFloat($(this).find('.average-rating').val());
                if (!isNaN(avg)) {
                    supportTotal += avg;
                    supportCount++;
                }
            });
            supportAverage = supportCount > 0 ? (supportTotal / supportCount) : 0;
        }

        let finalRating = 0;
        let strategicWeighted = 0;
        let coreWeighted = 0;
        let supportWeighted = 0;

        if (computationType === 'Type1') {
            strategicWeighted = strategicAverage * 0.45;
            coreWeighted = coreAverage * 0.55;
            finalRating = strategicWeighted + coreWeighted;
        } else { // Type2
            strategicWeighted = strategicAverage * 0.45;
            coreWeighted = coreAverage * 0.45;
            supportWeighted = supportAverage * 0.10;
            finalRating = strategicWeighted + coreWeighted + supportWeighted;
        }
        
        $('#strategic-avg-display').text(strategicWeighted.toFixed(2));
        $('#core-avg-display').text(coreWeighted.toFixed(2));
        if (computationType === 'Type2') {
            $('#support-avg-display').text(supportWeighted.toFixed(2));
        }

        $('#final-rating-display').text(finalRating.toFixed(2));
        $('#adjectival-rating').text(getRatingInterpretation(finalRating));
    }

    function buildRow(entry, category) {
        const cat_prefix = category.toLowerCase();
        // For employee view, MFO and Indicators are disabled. Accomplishments and ratings are enabled.
        return `
        <tr class="function-row ${cat_prefix}-function-row" data-category="${cat_prefix}">
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_mfo[]" readonly>${entry.mfo || ''}</textarea></td>
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_success_indicators[]" readonly>${entry.success_indicators || ''}</textarea></td>
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_accomplishments[]">${entry.accomplishments || ''}</textarea></td>
            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${cat_prefix}_q[]" min="1" max="5" step="1" maxlength="1" value="${entry.q || ''}"></td>
            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${cat_prefix}_e[]" min="1" max="5" step="1" maxlength="1" value="${entry.e || ''}"></td>
            <td><input type="number" class="form-control form-control-sm rating-input self-rating" name="${cat_prefix}_t[]" min="1" max="5" step="1" maxlength="1" value="${entry.t || ''}"></td>
            <td><input type="text" class="form-control form-control-sm average-rating" name="${cat_prefix}_a[]" value="${entry.a || ''}" readonly></td>
            <td><textarea class="form-control form-control-sm" name="${cat_prefix}_remarks[]" readonly>${entry.remarks || ''}</textarea></td>
        </tr>
        `;
    }
    
    function renderTable() {
        const tableBody = $('#ipcr-table-body');
        tableBody.empty(); // Clear existing rows
        
        // Strategic Functions
        if (content.strategic_functions && content.strategic_functions.length > 0) {
            tableBody.append('<tr><td colspan="8" class="text-start bg-light fw-bold">I. STRATEGIC FUNCTIONS</td></tr>');
            content.strategic_functions.forEach(entry => tableBody.append(buildRow(entry, 'strategic')));
        }
        
        // Core Functions
        if (content.core_functions && content.core_functions.length > 0) {
            tableBody.append('<tr><td colspan="8" class="text-start bg-light fw-bold">II. CORE FUNCTIONS</td></tr>');
            content.core_functions.forEach(entry => tableBody.append(buildRow(entry, 'core')));
        }

        // Support Functions
        if (computationType === 'Type2' && content.support_functions && content.support_functions.length > 0) {
            tableBody.append('<tr><td colspan="8" class="text-start bg-light fw-bold">III. SUPPORT FUNCTIONS</td></tr>');
            content.support_functions.forEach(entry => tableBody.append(buildRow(entry, 'support')));
        }
        calculateAllAverages();
        updateFinalRatingSummary();
    }
    
    function calculateAverage(row) {
        const q = parseFloat($(row).find('.self-rating[name$="_q[]"]').val()) || 0;
        const e = parseFloat($(row).find('.self-rating[name$="_e[]"]').val()) || 0;
        const t = parseFloat($(row).find('.self-rating[name$="_t[]"]').val()) || 0;
        
        let count = 0;
        if (q > 0) count++;
        if (e > 0) count++;
        if (t > 0) count++;

        const average = count > 0 ? ((q + e + t) / count).toFixed(2) : '';
        $(row).find('.average-rating').val(average);
    }

    function calculateAllAverages() {
        $('.function-row').each(function() {
            calculateAverage(this);
        });
    }

    // Event delegation for rating inputs
    $('#ipcr-table-body').on('input', '.rating-input', function() {
        // Restrict input to single digit 1-5
        let val = $(this).val();
        val = val.replace(/[^1-5]/g, ''); // Allow only digits 1-5
        if (val.length > 1) val = val.slice(0, 1); // Limit to 1 character
        $(this).val(val);

        const row = $(this).closest('tr');
        calculateAverage(row);
        updateFinalRatingSummary();
    });

    // Form submission serialization
    $('#fill-ipcr-form').on('submit', function(e) {
        let missingFields = false;
        
        // Check editable fields (Self Ratings & Accomplishments)
        $('.self-rating:visible').not('[readonly]').each(function() {
            if ($(this).val() === '') missingFields = true;
        });
        $('textarea[name*="_accomplishments[]"]:visible').not('[readonly]').each(function() {
            if ($(this).val().trim() === '') missingFields = true;
        });

        if (missingFields) {
            e.preventDefault();
            alert('Please fill in all required fields (Accomplishments and Ratings 1-5) before submitting.');
            return false;
        }

        updateFinalRatingSummary();
        // Create a deep copy to avoid modifying the original `content` object
        let updatedContent = JSON.parse(JSON.stringify(content));

        function updateRowData(category) {
            if (!updatedContent[`${category}_functions`]) return;
            
            $(`tr.${category}-function-row`).each(function(index, row) {
                if (updatedContent[`${category}_functions`][index]) {
                    updatedContent[`${category}_functions`][index].accomplishments = $(row).find(`textarea[name="${category}_accomplishments[]"]`).val();
                    updatedContent[`${category}_functions`][index].q = $(row).find(`input[name="${category}_q[]"]`).val();
                    updatedContent[`${category}_functions`][index].e = $(row).find(`input[name="${category}_e[]"]`).val();
                    updatedContent[`${category}_functions`][index].t = $(row).find(`input[name="${category}_t[]"]`).val();
                    updatedContent[`${category}_functions`][index].a = $(row).find(`input[name="${category}_a[]"]`).val();
                }
            });
        }
        
        updateRowData('strategic');
        updateRowData('core');
        if (updatedContent.computation_type === 'Type2') {
            updateRowData('support');
        }
        
        // Add summary to content
        updatedContent.summary = {
            strategic_average: $('#strategic-avg-display').text(),
            core_average: $('#core-avg-display').text(),
            support_average: $('#support-avg-display').text(),
            final_rating: $('#final-rating-display').text(),
            adjectival_rating: $('#adjectival-rating').text()
        };

        $('#form-content').val(JSON.stringify(updatedContent));
    });

    // Initial render
    renderTable();
});
</script>

<?php
// Include footer
include_once('includes/footer.php');
ob_end_flush();
?>
