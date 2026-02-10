<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Print Layout</title>
    <style>
        /* Define variables for the page layout */
        :root {
            --print-size: letter landscape;
        }

        /* The @page rule controls the physical paper size/orientation */
        @page {
            size: var(--print-size);
            margin: 1cm;
        }

        /* Basic styling for the screen */
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        .controls { background: #f4f4f4; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .preview-box { border: 2px dashed #ccc; padding: 20px; background: white; }
        
        /* Hide UI elements when printing */
        @media print {
            .controls { display: none; }
            body { padding: 0; }
            .preview-box { border: none; }
        }
    </style>
</head>
<body>

    <div class="controls">
        <h3>Print Settings</h3>
        <label>Select Form Type:</label>
        <select id="formType">
            <option value="form1">Form 1 (Letter Landscape)</option>
            <option value="form2">Form 2 (Legal Portrait)</option>
        </select>
        <br><br>
        <button onclick="handlePrint()">Print Document</button>
    </div>

    <div class="preview-box">
        <h1 id="displayTitle">Document Preview</h1>
        <p>This content will be formatted based on your selection.</p>
        <p>Current Setting: <strong id="currentSetting">Letter Landscape</strong></p>
        <div style="height: 500px; background: #eee; display: flex; align-items: center; justify-content: center;">
            [Form Content Area]
        </div>
    </div>

    <script>
        function handlePrint() {
            const formType = document.getElementById('formType').value;
            const root = document.documentElement;
            const settingText = document.getElementById('currentSetting');

            if (formType === 'form1') {
                // Set to Letter Landscape
                root.style.setProperty('--print-size', 'letter landscape');
                settingText.innerText = "Letter Landscape";
            } else {
                // Set to Legal Portrait
                root.style.setProperty('--print-size', 'legal portrait');
                settingText.innerText = "Legal Portrait";
            }

            // Small delay to ensure CSS variable is applied before the print dialog opens
            setTimeout(() => {
                window.print();
            }, 100);
        }
    </script>
</body>
</html>