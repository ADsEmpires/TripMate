<?php
// sql.php - SIMPLIFIED Database Admin (Improved Version 2025)
// Enforces required fields + skips auto-timestamp columns on insert

session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$conn = mysqli_connect('localhost', 'root', '', 'tripmate');
if (!$conn) die("Connection failed: " . mysqli_connect_error());

// Upload directory
$UPLOAD_DIR = 'uploads/';
if (!is_dir($UPLOAD_DIR)) {
    mkdir($UPLOAD_DIR, 0777, true);
}

// AJAX duplicate check endpoint
if (isset($_POST['ajax_check'])) {
    header('Content-Type: application/json');
    
    $table  = $_POST['table']  ?? '';
    $field  = $_POST['field']  ?? '';
    $value  = $_POST['value']  ?? '';
    $exclude_id = (int)($_POST['exclude_id'] ?? 0);
    
    $response = ['exists' => false];
    
    if ($table && $field && $value !== '') {
        $value = mysqli_real_escape_string($conn, $value);
        $sql = "SELECT COUNT(*) as count FROM `$table` WHERE `$field` = '$value'";
        if ($exclude_id > 0) $sql .= " AND id != $exclude_id";
        
        $result = mysqli_query($conn, $sql);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            $response['exists'] = $row['count'] > 0;
            $response['count']  = $row['count'];
        } else {
            $response['error'] = mysqli_error($conn);
        }
    }
    echo json_encode($response);
    exit();
}

// Get parameters
$table  = $_GET['table']  ?? '';
$action = $_GET['action'] ?? 'view';
$id     = (int)($_GET['id'] ?? 0);

// Get all tables
$tables_result = mysqli_query($conn, "SHOW TABLES");
$tables = [];
while ($row = mysqli_fetch_array($tables_result)) $tables[] = $row[0];

// Process form (ADD / UPDATE)
$message = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax_check'])) {
    if (isset($_POST['add']) || isset($_POST['update'])) {
        $table_name = $_POST['table'] ?? '';
        $is_update  = isset($_POST['update']);
        $record_id  = $is_update ? (int)($_POST['id'] ?? 0) : 0;
        
        // Get table structure once
        $field_result = mysqli_query($conn, "SHOW COLUMNS FROM `$table_name`");
        $field_info = [];
        while ($row = mysqli_fetch_assoc($field_result)) {
            $field_info[$row['Field']] = $row;
        }
        
        unset($_POST['add'], $_POST['update'], $_POST['table']);
        if ($is_update) unset($_POST['id']);
        
        // ------------------- VALIDATION: Required fields -------------------
        foreach ($field_info as $fname => $finfo) {
            if (strpos($finfo['Extra'], 'auto_increment') !== false) continue;
            if ($finfo['Null'] !== 'NO') continue; // allow NULL
            
            // You can add exceptions here if needed
            if (in_array($fname, ['profile_pic', 'image', 'notes', 'description'])) continue;
            
            if (!isset($_POST[$fname]) || trim($_POST[$fname]) === '') {
                $label = ucfirst(str_replace('_', ' ', $fname));
                $errors[] = "$label is required.";
            }
        }
        
        // Stop if validation failed
        if (!empty($errors)) {
            $message = '<div class="error"><strong>Cannot save:</strong><ul><li>' 
                     . implode('</li><li>', $errors) . '</li></ul></div>';
        } else {
            // ------------------- Handle file upload -------------------
            $uploaded_file = null;
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_pic'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','gif','webp'];
                if (in_array($ext, $allowed)) {
                    $filename = uniqid('pic_') . '.' . $ext;
                    $target = $UPLOAD_DIR . $filename;
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $uploaded_file = $target;
                        // Delete old file if updating
                        if ($is_update && !empty($_POST['old_profile_pic'])) {
                            @unlink($_POST['old_profile_pic']);
                        }
                    }
                }
            }
            
            // Keep old image if no new upload
            if ($is_update && !$uploaded_file && !empty($_POST['old_profile_pic'])) {
                $uploaded_file = $_POST['old_profile_pic'];
            }
            
            // Prepare data
            $data = $_POST;
            if ($uploaded_file !== null) {
                $data['profile_pic'] = $uploaded_file; // or 'image' depending on column name
            }
            
            if ($is_update) {
                // UPDATE
                $updates = [];
                foreach ($data as $col => $val) {
                    if (strpos($col, 'old_') === 0) continue;
                    $val_esc = $val === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $val) . "'";
                    $updates[] = "`$col` = $val_esc";
                }
                $sql = "UPDATE `$table_name` SET " . implode(', ', $updates) . " WHERE id = $record_id";
            } else {
                // INSERT - skip auto-managed timestamp columns
                $cols = $vals = [];
                $skip_auto = ['created_at', 'updated_at', 'timestamp', 'date_added', 'last_login'];
                
                foreach ($data as $col => $val) {
                    if (strpos($col, 'old_') === 0) continue;
                    if (in_array($col, $skip_auto)) continue;
                    
                    $cols[] = "`$col`";
                    $vals[] = $val === '' ? 'NULL' : "'" . mysqli_real_escape_string($conn, $val) . "'";
                }
                
                $sql = "INSERT INTO `$table_name` (" . implode(', ', $cols) . ") 
                        VALUES (" . implode(', ', $vals) . ")";
            }
            
            if (mysqli_query($conn, $sql)) {
                $message = $is_update 
                    ? '<div class="success">Record updated successfully!</div>'
                    : '<div class="success">Record added! (ID: ' . mysqli_insert_id($conn) . ')</div>';
            } else {
                $message = '<div class="error">Database error: ' . mysqli_error($conn) . '</div>';
            }
        }
    }
}

// DELETE
if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // Delete associated file if exists
    $check_sql = "SELECT profile_pic FROM `$table` WHERE id = $del_id";
    $check_res = mysqli_query($conn, $check_sql);
    if ($check_res && $row = mysqli_fetch_assoc($check_res)) {
        if (!empty($row['profile_pic']) && file_exists($row['profile_pic'])) {
            @unlink($row['profile_pic']);
        }
    }
    
    $sql = "DELETE FROM `$table` WHERE id = $del_id";
    if (mysqli_query($conn, $sql)) {
        $message = '<div class="success">Record deleted successfully!</div>';
    } else {
        $message = '<div class="error">Delete failed: ' . mysqli_error($conn) . '</div>';
    }
}

// Load table data
$data = $field_info = $edit_data = [];
if ($table && in_array($table, $tables)) {
    $field_result = mysqli_query($conn, "SHOW COLUMNS FROM `$table`");
    while ($row = mysqli_fetch_assoc($field_result)) {
        $field_info[] = $row;
    }
    
    $data_result = mysqli_query($conn, "SELECT * FROM `$table` ORDER BY id DESC LIMIT 50");
    while ($row = mysqli_fetch_assoc($data_result)) $data[] = $row;
    
    if ($action === 'edit' && $id > 0) {
        $edit_result = mysqli_query($conn, "SELECT * FROM `$table` WHERE id = $id");
        $edit_data = mysqli_fetch_assoc($edit_result) ?: [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Manager</title>
    <style>
        body { font-family: Arial, sans-serif; margin:20px; background:#f8f9fa; }
        .container { max-width:1200px; margin:0 auto; background:white; padding:20px; border-radius:8px; box-shadow:0 2px 12px rgba(0,0,0,0.08); }
        h1 { color:#2c3e50; border-bottom:3px solid #27ae60; padding-bottom:10px; }
        .nav { background:#ecf0f1; padding:15px; border-radius:6px; margin-bottom:20px; display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .btn { padding:8px 16px; border:none; border-radius:4px; color:white; text-decoration:none; font-size:14px; cursor:pointer; }
        .btn-primary   { background:#3498db; }
        .btn-success   { background:#27ae60; }
        .btn-danger    { background:#e74c3c; }
        .btn-warning   { background:#f39c12; }
        .btn:hover     { opacity:0.9; }
        .btn-sm        { padding:5px 10px; font-size:12px; }
        table { width:100%; border-collapse:collapse; margin:20px 0; }
        th { background:#27ae60; color:white; padding:12px; text-align:left; }
        td { padding:10px; border-bottom:1px solid #eee; }
        tr:hover { background:#f9f9f9; }
        .message { padding:12px; margin:15px 0; border-radius:4px; }
        .success { background:#e8f5e9; color:#2e7d32; border:1px solid #c8e6c9; }
        .error   { background:#ffebee; color:#c62828; border:1px solid #ef9a9a; }
        .form-group { margin-bottom:18px; }
        .form-group label { display:block; font-weight:bold; margin-bottom:6px; color:#444; }
        input, textarea, select { width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; }
        .file-input { padding:6px; }
        .preview img { max-width:120px; margin-top:8px; border-radius:4px; border:1px solid #ddd; }
        .invalid { border-color:#e74c3c !important; background:#fff5f5; }
        .valid   { border-color:#27ae60 !important; background:#f0fff4; }
        .check-feedback { font-size:13px; margin-top:4px; min-height:18px; }
        .check-good { color:#27ae60; }
        .check-bad  { color:#e74c3c; }
        .check-wait { color:#f39c12; }
    </style>
</head>
<body>

<div class="container">
    <h1>Database Manager</h1>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <div class="nav">
        <select onchange="if(this.value) location.href='?table='+this.value">
            <option value="">— Select Table —</option>
            <?php foreach($tables as $t): ?>
                <option value="<?= $t ?>" <?= $t===$table ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
            <?php endforeach; ?>
        </select>

        <?php if ($table): ?>
            <a href="?table=<?= $table ?>" class="btn btn-primary">View</a>
            <a href="?table=<?= $table ?>&action=add" class="btn btn-success">Add New</a>
            <a href="admin_dasbord.php" class="btn btn-warning">Back to Dashboard</a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <h2><?= $action==='add' ? 'Add New Record' : 'Edit Record' ?> → <?= htmlspecialchars($table) ?></h2>

        <form method="POST" enctype="multipart/form-data" id="recordForm">
            <input type="hidden" name="table" value="<?= htmlspecialchars($table) ?>">
            <input type="hidden" id="current_id" value="<?= $id ?>">

            <?php if ($action==='edit'): ?>
                <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <?php foreach ($field_info as $field):
                $fname = $field['Field'];
                if ($action==='add' && strpos($field['Extra'], 'auto_increment') !== false) continue;

                $value = $edit_data[$fname] ?? '';
                $required = ($field['Null']==='NO' && $fname !== 'id' && $action==='add') ? 'required' : '';
                $should_check = in_array($fname, ['email','username','phone','name']);
            ?>
                <div class="form-group">
                    <label><?= ucfirst(str_replace('_',' ',$fname)) ?>
                        <?php if ($required): ?><span style="color:#e74c3c">*</span><?php endif; ?>
                    </label>

                    <?php if ($fname === 'profile_pic' || $fname === 'image'): ?>
                        <input type="file" name="<?= $fname ?>" id="field_<?= $fname ?>" class="file-input" accept="image/*">
                        <?php if ($action==='edit' && $value): ?>
                            <input type="hidden" name="old_<?= $fname ?>" value="<?= htmlspecialchars($value) ?>">
                            <div class="preview">
                                <img src="<?= htmlspecialchars($value) ?>" alt="Current" onerror="this.style.display='none'">
                            </div>
                        <?php endif; ?>

                    <?php elseif (strpos($field['Type'], 'enum') === 0): ?>
                        <?php
                        preg_match("/^enum\((.*)\)$/", $field['Type'], $m);
                        $opts = explode(',', str_replace("'", '', $m[1] ?? ''));
                        ?>
                        <select name="<?= $fname ?>" <?= $required ?>>
                            <option value="">— Select —</option>
                            <?php foreach ($opts as $opt): ?>
                                <option value="<?= $opt ?>" <?= $value===$opt ? 'selected' : '' ?>>
                                    <?= ucfirst($opt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif (strpos($field['Type'], 'text') !== false || strpos($field['Type'], 'mediumtext') !== false): ?>
                        <textarea name="<?= $fname ?>" rows="4" <?= $required ?>><?= htmlspecialchars($value) ?></textarea>

                    <?php else: ?>
                        <input type="<?= strpos($fname,'password')!==false ? 'password' : 'text' ?>"
                               name="<?= $fname ?>"
                               id="field_<?= $fname ?>"
                               value="<?= htmlspecialchars($value) ?>"
                               <?= $required ?>
                               <?= $should_check ? 'onkeyup="checkField(\''.$fname.'\', this.value)"' : '' ?>>
                    <?php endif; ?>

                    <?php if ($should_check): ?>
                        <div id="feedback_<?= $fname ?>" class="check-feedback"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <div style="margin-top:25px;">
                <?php if ($action==='add'): ?>
                    <button type="submit" name="add" class="btn btn-success">Save New Record</button>
                <?php else: ?>
                    <button type="submit" name="update" class="btn btn-primary">Update Record</button>
                <?php endif; ?>
                <a href="?table=<?= $table ?>" class="btn btn-danger">Cancel</a>
            </div>
        </form>

        <script>
        let timeout = null;

        function checkField(field, value) {
            if (!value.trim()) {
                document.getElementById('field_'+field).className = '';
                document.getElementById('feedback_'+field).innerHTML = '';
                return;
            }

            if (field.includes('password')) return;

            const input = document.getElementById('field_'+field);
            input.className = 'checking';
            document.getElementById('feedback_'+field).innerHTML = '<span class="check-wait">Checking…</span>';

            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('ajax_check', '1');
                formData.append('table', '<?= $table ?>');
                formData.append('field', field);
                formData.append('value', value);
                formData.append('exclude_id', document.getElementById('current_id').value || 0);

                fetch('', { method:'POST', body:formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.exists) {
                            input.className = 'invalid';
                            document.getElementById('feedback_'+field).innerHTML =
                                '<span class="check-bad">Already exists ('+data.count+')</span>';
                        } else {
                            input.className = 'valid';
                            document.getElementById('feedback_'+field).innerHTML =
                                '<span class="check-good">✓ Available</span>';
                        }
                    })
                    .catch(() => {
                        input.className = '';
                        document.getElementById('feedback_'+field).innerHTML = '<span class="check-bad">Check failed</span>';
                    });
            }, 600);
        }

        document.getElementById('recordForm')?.addEventListener('submit', e => {
            const invalid = document.querySelectorAll('.invalid');
            if (invalid.length > 0) {
                e.preventDefault();
                alert('Cannot save — duplicate values detected in: ' + 
                      Array.from(invalid).map(el => el.name || el.id.replace('field_','')).join(', '));
            }
        });

        // Auto-check on load for edit mode
        document.addEventListener('DOMContentLoaded', () => {
            ['email','username','name','phone'].forEach(f => {
                const el = document.getElementById('field_'+f);
                if (el && el.value.trim()) checkField(f, el.value);
            });

            // Auto-hide messages after 6 seconds
            setTimeout(() => {
                document.querySelectorAll('.message').forEach(m => m.style.display='none');
            }, 6000);
        });
        </script>

    <?php elseif ($table && $field_info): ?>
        <h2><?= htmlspecialchars($table) ?> Table (<?= count($data) ?> records shown)</h2>

        <?php if (empty($data)): ?>
            <p style="text-align:center; color:#777; padding:40px 0;">
                No records found.<br><br>
                <a href="?table=<?= $table ?>&action=add" class="btn btn-success">Create First Record</a>
            </p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($field_info as $f): ?>
                                <th><?= htmlspecialchars($f['Field']) ?></th>
                            <?php endforeach; ?>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($field_info as $f):
                                    $val = $row[$f['Field']] ?? '';
                                    $is_img = stripos($f['Field'], 'pic') !== false || stripos($f['Field'], 'image') !== false;
                                ?>
                                    <td>
                                        <?php if ($is_img && $val): ?>
                                            <img src="<?= htmlspecialchars($val) ?>" style="width:38px;height:38px;object-fit:cover;border-radius:4px;" onerror="this.style.display='none'">
                                        <?php elseif (stripos($f['Field'], 'password') !== false): ?>
                                            ••••••••
                                        <?php elseif (strlen($val) > 45): ?>
                                            <?= htmlspecialchars(substr($val,0,42)) ?>…
                                        <?php else: ?>
                                            <?= htmlspecialchars($val) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <a href="?table=<?= $table ?>&action=edit&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <a href="?table=<?= $table ?>&delete=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Really delete this record?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <h2>Select a Table to Manage</h2>
        <p style="color:#555;">Total tables: <?= count($tables) ?></p>

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:16px; margin:20px 0;">
            <?php foreach ($tables as $t): 
                $cnt = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) cnt FROM `$t`"))['cnt'];
            ?>
                <div style="background:#fff; padding:16px; border:1px solid #ddd; border-radius:6px; text-align:center;">
                    <a href="?table=<?= $t ?>" style="font-weight:bold; color:#3498db; text-decoration:none;"><?= htmlspecialchars($t) ?></a>
                    <div style="margin-top:6px; color:#777; font-size:13px;"><?= $cnt ?> records</div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

</body>
</html>

<?php mysqli_close($conn); ?>