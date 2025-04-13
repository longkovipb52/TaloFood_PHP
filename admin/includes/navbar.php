<?php
// Lấy thông tin admin
$stmt = $conn->prepare("SELECT * FROM Account WHERE account_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <button type="button" id="sidebarCollapse" class="btn btn-primary">
            <i class="fas fa-bars"></i>
        </button>

        <div class="d-flex align-items-center">
            <div class="dropdown">
                <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="dropdownMenuButton" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                    <span><?php echo htmlspecialchars($admin['username']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Hồ Sơ</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Cài Đặt</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Đăng Xuất</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav> 