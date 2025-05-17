<?php
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
} 

$admin_id = $_SESSION['user_id'];

// Fetch admin details from database
include '../db.php';
$stmt = $connection->prepare("SELECT username, first_name, middle_initial, last_name FROM tbl_admin WHERE id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $connection->error);
}

$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $admin = $result->fetch_assoc();
    $username = $admin['username'];
    $first_name = $admin['first_name'];
    $middle_initial = $admin['middle_initial'];
    $last_name = $admin['last_name'];
    
    // Construct display name
    $display_name = $first_name;
    if (!empty($middle_initial)) {
        $display_name .= ' ' . $middle_initial . '.';
    }
    $display_name .= ' ' . $last_name;
} else {
    $display_name = 'admin';
}

// Function to get initials from first and last name
function getInitials($first_name, $last_name) {
    $initials = '';
    if (!empty($first_name)) {
        $initials .= strtoupper(substr($first_name, 0, 1));
    }
    if (!empty($last_name)) {
        $initials .= strtoupper(substr($last_name, 0, 1));
    }
    return $initials;
}

mysqli_close($connection);
?>

<body>
<div class="sidebar">
    <div class="profile-section text-center">
        <div class="avatar">
            <?php echo htmlspecialchars(getInitials($first_name, $middle_initial, $last_name)); ?>
        </div>
        <h4 class="admin-name"><?php echo htmlspecialchars($display_name); ?></h4>
    </div>
    <nav>        
    <ul>
        <li <?php echo basename($_SERVER['PHP_SELF']) == 'admin_homepage.php' ? 'class="active"' : ''; ?>> 
            <a href="admin_homepage.php">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
        </li>
        <li <?php echo basename($_SERVER['PHP_SELF']) == 'admin_profile.php' ? 'class="active"' : ''; ?>>
            <a href="admin_profile.php">
                <i class="fas fa-user-circle"></i>
                <span>My Profile</span>
            </a>
        </li>
        <li <?php echo basename($_SERVER['PHP_SELF']) == 'help_support.php' ? 'class="active"' : ''; ?>>
            <a href="help_support.php">
                <i class="fas fa-question-circle"></i>
                <span>Help & Support</span>
            </a>
        </li>
        <li>
            <a href="../login.php" onclick="return confirmLogout()">
                <i class="fas fa-sign-out-alt"></i>
                <span>Log Out</span>
            </a>
        </li>
    </ul>
</nav>
</div>

<style>
    :root {
    --sidebar-width: 250px;

}


body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
    color: #333;
    min-height: 100vh;
    display: flex;
}
.sidebar {
    background-color: #007855;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    width: var(--sidebar-width);
    padding: 20px 0;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    overflow-y: auto;
    z-index: 1000;

}

.profile-section {
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}


.avatar {
  width: 80px;
  height: 80px;
  background-color:rgb(238, 238, 239);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  font-weight: bold;
  color: black;
  text-transform: uppercase;
  user-select: none;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
.admin-name {
    margin-top: 15px;
    font-size: 16px;
    color: #ecf0f1;
    font-weight: 500;
}

.sidebar nav ul {
    list-style-type: none;
    padding: 0;
    margin: 20px 0;
}

.sidebar nav ul li a {
    display: block;
    padding: 12px 20px;
    color: #ecf0f1;
    text-decoration: none;
    transition: background-color 0.3s ease;
    border-left: 4px solid transparent;
}
.sidebar nav ul li a:hover, .sidebar nav ul li a.active {
    background-color: #008F57;
    border-left-color:rgb(247, 252, 247); /* Lighter orange for sidebar accents */
}

.header {
    background:white;
    width: calc(100% - var(--sidebar-width));
    padding: 0 20px;
    color: #1b651b;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
    font-family: 'poppins', serif;
    letter-spacing: clamp(2px, 1vw, 5px);
    position: absolute;
    top: 0;
    left: var(--sidebar-width);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 60px;
    transition: all 0.3s ease;
}

.header h1 {
    margin: 0;
    font-size: clamp(1.25rem, 2vw + 0.5rem, 2rem);
    font-weight: 400;
    flex-grow: 1;
    text-align: left;
}



.main-content {
    margin-left: 250px;
    padding: 80px 20px 70px;
    flex: 1;
    min-height: calc(100vh - 130px);
    box-sizing: border-box;
}

/* Footer Styles */
.footer {
    margin-top: 40px;
    padding: 10px;
    text-align: center;
    background-color: white;
    box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
    position: fixed;
    bottom: 0;
    left: var(--sidebar-width);
    right: 0;
    z-index: 998;
    transition: all 0.3s ease;
}

.footer p {
    margin: 0;
    color: #666;
    font-size: 14px;
    line-height: 1.5;
}


@media screen and (max-width: 992px) {
    .footer {
        left: 0;
    }
}

@media screen and (max-width: 576px) {
    .footer {
        padding: 15px 10px;
    }
    
    .footer p {
        font-size: 12px;
    }
}


.notification-icon {
    font-size: 24px;
    color: white;
    cursor: pointer;
    margin-left: 20px;
    transition: color 0.3s ease;
}

.notification-icon:hover {
    color: #f2f2f2;
}

.menu-toggle {
    display: none;
    background: none;
    border: none;
    color: #1b651b;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    margin-left: -20px;
}
@media screen and (max-width: 768px) {
    .header {
        left: 0;
        width: 100%;
    }

    .menu-toggle {
        display: block;
    }

    .header h1 {
        font-size: 20px;
        margin-left: 40px; /* Space for menu toggle */
    }
}

@media screen and (max-width: 480px) {
    .header h1 {
        font-size: 16px;
    }

    .notification-icon {
        font-size: 20px;
    }
}
/* Responsive breakpoints */
@media screen and (max-width: 1024px) {
    .button-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media screen and (max-width: 768px) {
    .menu-toggle {
        display: block;
    }

    .sidebar {
        transform: translateX(-100%);
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .header {
        left: 0;
        width: 100%;
        padding-left: 60px;
    }

    .main-content {
        margin-left: 0;
    }

    .footer {
        left: 0;
    }

    .welcome-text {
        padding: 30px;
        margin-left: -20;
        
    }

    .welcome-text h1 {
        font-size: 36px;
    }
}

@media screen and (max-width: 480px) {
    .profile-image {
        width: 80px;
        height: 80px;
    }

    .header h1 {
        font-size: 18px;
    }

    .welcome-text {
        padding: 30px;
    }

    .welcome-text h1 {
        font-size: 28px;
    }

    .button-container {
        grid-template-columns: 1fr;
        padding: 10px;
    }

    .custom-btn {
        height: 60px;
        padding: 15px;
    }

    .notification-panel {
        width: 90%;
        right: 5%;
        left: 5%;
    }
}
</style>

<script>
function confirmLogout() {
    return confirm('Are you sure you want to log out?');
}
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    if (window.innerWidth <= 768 && 
        !sidebar.contains(event.target) && 
        !menuToggle.contains(event.target)) {
        sidebar.classList.remove('active');
    }
});
</script>