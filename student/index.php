<?php
session_start();
include '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$std_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
// Student data fetching removed as sidebar is gone

// ดึงรายการบริษัทที่ถูกบันทึกไว้ของนักศึกษานี้ (เพื่อทำให้ปุ่มแสดงสถานะ)
$savedIds = [];
if ($std_id) {
    $sStmt = $conn->prepare("SELECT com_id FROM tb_saved WHERE std_id = ?");
    if ($sStmt) {
        $sStmt->bind_param('s', $std_id);
        $sStmt->execute();
        $sRes = $sStmt->get_result();
        if ($sRes) {
            while ($r = $sRes->fetch_assoc()) $savedIds[] = (int)$r['com_id'];
        }
        $sStmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internfinder - ระบบค้นหาที่ฝึกงาน</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>



<body>
    <?php include 'includes/header.php'; ?>







    <main>
        <!-- Banner Section -->
        <div style="
            background: url('../img/482325862_4087742598149812_4768157798699933898_n.jpg') center/cover no-repeat;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            min-height: 250px;
            width: 100%;
        ">
        </div>

        <?php
            // Read search/filter GET params and set defaults for the UI
            $q_val = isset($_GET['q']) ? trim($_GET['q']) : '';
            $selected_dep = isset($_GET['dep']) ? (int)$_GET['dep'] : 0;
            $order_val = (isset($_GET['order']) && $_GET['order'] === 'old') ? 'old' : 'new';
        ?>

        <form method="GET" class="filter-bar">
            <input type="text" name="q" class="search-input" placeholder="ค้นหาชื่อบริษัท, ที่อยู่ หรือเบอร์โทร..." value="<?php echo htmlspecialchars($q_val); ?>">
            
            <select name="dep" class="select-input">
                <option value="0" <?php if($selected_dep == 0) echo 'selected'; ?>>ทุกแผนก</option>
                <?php 
                $depSql = "SELECT * FROM tb_department ORDER BY dep_name ASC";
                $depRes = mysqli_query($conn, $depSql);
                while($d = mysqli_fetch_assoc($depRes)): 
                ?>
                    <option value="<?php echo $d['dep_id']; ?>" <?php if($selected_dep == $d['dep_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($d['dep_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>

            <select name="order" class="select-input">
                <option value="new" <?php echo $order_val === 'new' ? 'selected' : ''; ?>>เรียงใหม่สุด</option>
                <option value="old" <?php echo $order_val === 'old' ? 'selected' : ''; ?>>เรียงเก่าสุด</option>
            </select>

            <button type="submit" class="btn-filter">
                <i class="fas fa-search"></i> ค้นหา
            </button>
        </form>

        <div class="page-title">
            <i class="fas fa-building"></i> 
            บริษัทที่เปิดรับสมัคร
        </div>

        <div class="company-list">
            <?php
            // Prepare search/filter values
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $dep = isset($_GET['dep']) ? (int)$_GET['dep'] : 0;
            $order = (isset($_GET['order']) && $_GET['order'] === 'old') ? 'ASC' : 'DESC';

            // Pagination setup: show 5 companies per page
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = 5;
            $offset = ($page - 1) * $perPage;

            $result = false;
            $total = 0;

            // Build prepared statements and include COUNT + LIMIT/OFFSET
            if ($q !== '' && $dep > 0) {
                $searchTerm = "%" . $q . "%";
                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tb_company WHERE (com_name LIKE ? OR com_add_no LIKE ? OR com_road LIKE ? OR com_subdistrict LIKE ? OR com_district LIKE ? OR com_province LIKE ? OR com_zipcode LIKE ? OR com_tel LIKE ?) AND dep_id = ?");
                if ($countStmt) {
                    $countStmt->bind_param("ssssssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $dep);
                    $countStmt->execute();
                    $cntRes = $countStmt->get_result();
                    $total = (int)($cntRes->fetch_assoc()['cnt'] ?? 0);
                    $countStmt->close();
                }

                $stmt = $conn->prepare("SELECT c.*, d.dep_name FROM tb_company c LEFT JOIN tb_department d ON c.dep_id = d.dep_id WHERE (c.com_name LIKE ? OR c.com_add_no LIKE ? OR c.com_road LIKE ? OR c.com_subdistrict LIKE ? OR c.com_district LIKE ? OR c.com_province LIKE ? OR c.com_zipcode LIKE ? OR c.com_tel LIKE ?) AND c.dep_id = ? ORDER BY c.com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
                if ($stmt) {
                    $stmt->bind_param("ssssssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $dep);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                }

            } elseif ($q !== '') {
                $searchTerm = "%" . $q . "%";
                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tb_company WHERE (com_name LIKE ? OR com_add_no LIKE ? OR com_road LIKE ? OR com_subdistrict LIKE ? OR com_district LIKE ? OR com_province LIKE ? OR com_zipcode LIKE ? OR com_tel LIKE ?)");
                if ($countStmt) {
                    $countStmt->bind_param("ssssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
                    $countStmt->execute();
                    $cntRes = $countStmt->get_result();
                    $total = (int)($cntRes->fetch_assoc()['cnt'] ?? 0);
                    $countStmt->close();
                }

                $stmt = $conn->prepare("SELECT c.*, d.dep_name FROM tb_company c LEFT JOIN tb_department d ON c.dep_id = d.dep_id WHERE (c.com_name LIKE ? OR c.com_add_no LIKE ? OR c.com_road LIKE ? OR c.com_subdistrict LIKE ? OR c.com_district LIKE ? OR c.com_province LIKE ? OR c.com_zipcode LIKE ? OR c.com_tel LIKE ?) ORDER BY c.com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
                if ($stmt) {
                    $stmt->bind_param('ssssssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                }

            } elseif ($dep > 0) {
                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tb_company WHERE dep_id = ?");
                if ($countStmt) {
                    $countStmt->bind_param('i', $dep);
                    $countStmt->execute();
                    $cntRes = $countStmt->get_result();
                    $total = (int)($cntRes->fetch_assoc()['cnt'] ?? 0);
                    $countStmt->close();
                }

                $stmt = $conn->prepare("SELECT c.*, d.dep_name FROM tb_company c LEFT JOIN tb_department d ON c.dep_id = d.dep_id WHERE c.dep_id = ? ORDER BY c.com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
                if ($stmt) {
                    $stmt->bind_param('i', $dep);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                }

            }

            // Fallback to a simple query if no prepared result yet
            if ($result === false) {
                $countRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tb_company");
                $total = (int)($countRes ? mysqli_fetch_assoc($countRes)['cnt'] : 0);

                $sql = "SELECT c.*, d.dep_name FROM tb_company c LEFT JOIN tb_department d ON c.dep_id = d.dep_id ORDER BY c.com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
                $result = mysqli_query($conn, $sql);
            }

            if ($result && mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
                    $c_id = (int)$row['com_id'];
                    $total_cap = 0;
                    $approved_count = 0;

                    // Fetch capacity and count from tb_company_detail & tb_internship separately but safely
                    $cap_stmt = $conn->prepare("SELECT SUM(job_capacity) as cap FROM tb_company_detail WHERE com_id = ?");
                    if ($cap_stmt) {
                        $cap_stmt->bind_param("i", $c_id);
                        $cap_stmt->execute();
                        $cap_res = $cap_stmt->get_result();
                        if ($cap_res) {
                            $cap_row = $cap_res->fetch_assoc();
                            $total_cap = (int)($cap_row['cap'] ?? 0);
                        }
                        $cap_stmt->close();
                    }

                    $app_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tb_internship WHERE com_id = ? AND status = 'approved'");
                    if ($app_stmt) {
                        $app_stmt->bind_param("i", $c_id);
                        $app_stmt->execute();
                        $app_res = $app_stmt->get_result();
                        if ($app_res) {
                            $app_row = $app_res->fetch_assoc();
                            $approved_count = (int)($app_row['cnt'] ?? 0);
                        }
                        $app_stmt->close();
                    }

                    $is_full = ($total_cap > 0 && $approved_count >= $total_cap);
                ?>
                <div class="card">
                    <div class="card-body">
                        <div class="company-logo">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="company-info">
                            <h3><?php echo htmlspecialchars($row['com_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($row['com_tel'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="address"><i class="fas fa-map-marker-alt"></i> 
                                <?php 
                                    $full_addr = ($row['com_add_no'] ? $row['com_add_no'] . ' ' : '') . ($row['com_road'] ? 'ถ.' . $row['com_road'] . ' ' : '') . ($row['com_subdistrict'] ? 'ต.' . $row['com_subdistrict'] . ' ' : '') . ($row['com_district'] ? 'อ.' . $row['com_district'] . ' ' : '') . ($row['com_province'] ? 'จ.' . $row['com_province'] . ' ' : '') . ($row['com_zipcode'] ?? '');
                                    echo htmlspecialchars(mb_strimwidth($full_addr ?: '-', 0, 45, "..."), ENT_QUOTES, 'UTF-8'); 
                                ?>
                            </p>
                        </div>
                        <button class="save-btn" type="button" data-com-id="<?php echo (int)$row['com_id']; ?>">
                            <i class="<?php echo in_array((int)$row['com_id'], $savedIds) ? 'fas' : 'far'; ?> fa-bookmark"></i>
                        </button>
                    </div>
                    <div class="card-footer">
                        <div class="tags-container">
                            <span class="tag" style="background: #eef2f7; color: #4F46E5;">
                                <i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($row['dep_name'] ?? 'ไม่ระบุ', ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <span class="tag" style="background: <?php echo $is_full ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo $is_full ? '#991b1b' : '#166534'; ?>;">
                                <i class="fas fa-users"></i> รับแล้ว <?php echo $approved_count; ?>/<?php echo $total_cap ?: 'ไม่จำกัด'; ?> คน
                                <?php echo $is_full ? ' (เต็ม)' : ''; ?>
                            </span>
                        </div>
                        <a href="details.php?id=<?php echo (int)$row['com_id']; ?>" class="view-more">ดูรายละเอียดงาน</a>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo "ไม่พบข้อมูลบริษัท";
            }

            // Render pagination UI
            $totalPages = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
            ?>

            <?php if ($total > 0 && $totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="page-summary">หน้า <?php echo $page; ?> จาก <?php echo $totalPages; ?> — รวม <?php echo $total; ?> รายการ</div>
                    <nav aria-label="การนำทางหน้า">
                      <ul class="pagination">
                        <?php $build = function($p){ $qs = $_GET; $qs['page'] = $p; return htmlspecialchars('?'.http_build_query($qs)); }; ?>
                        <?php $prev = max(1, $page-1); ?>
                        <li class="page-item"><a class="page-link" href="<?php echo $build($prev); ?>"><i class="fas fa-chevron-left"></i></a></li>

                        <?php
                            $maxLinks = 5;
                            $start = max(1, $page - intval(floor($maxLinks / 2)));
                            $end = min($totalPages, $start + $maxLinks - 1);
                            if ($end - $start + 1 < $maxLinks) {
                                $start = max(1, $end - $maxLinks + 1);
                            }

                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="'.$build(1).'">1</a></li>'; 
                                if ($start > 2) echo '<li class="page-item"><span class="page-link">…</span></li>'; 
                            }

                            for ($p = $start; $p <= $end; $p++):
                        ?>
                            <li class="page-item"><a class="page-link <?php if ($p == $page) echo 'active'; ?>" href="<?php echo $build($p); ?>"><?php echo $p; ?></a></li>
                        <?php endfor;

                            if ($end < $totalPages) {
                                if ($end < $totalPages-1) echo '<li class="page-item"><span class="page-link">…</span></li>'; 
                                echo '<li class="page-item"><a class="page-link" href="'.$build($totalPages).'">'.$totalPages.'</a></li>'; 
                            }
                        ?>

                        <?php $next = min($totalPages, $page+1); ?>
                        <li class="page-item"><a class="page-link" href="<?php echo $build($next); ?>"><i class="fas fa-chevron-right"></i></a></li>
                      </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </main>

   



</body>
</html>