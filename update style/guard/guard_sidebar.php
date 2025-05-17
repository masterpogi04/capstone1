<?php

include '../db.php';
// Removed the incomplete "incl" line that was causing the error

// Ensure user is logged in and is a guard
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'guard') {
    header("Location: ../login.php");
    exit();
}

// Get the guard's ID from the session
$guard_id = $_SESSION['user_id'];

// Fetch the guard's details
$stmt = $connection->prepare("SELECT first_name, last_name, profile_picture FROM tbl_guard WHERE id = ?");
$stmt->bind_param("i", $guard_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $guard = $result->fetch_assoc();
    $name = $guard['first_name'] . ' ' . $guard['last_name'];
    $profile_picture = $guard['profile_picture'];
} else {
    die("Guard not found.");
}
?>
<div class="sidebar">
<div class="profile-section text-center">
        <!-- Display profile picture and name -->
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" class="profile-image"  alt="Profile Picture" style="width: 100px; height: 100px; object-fit: cover;">
        <h4 class="guard-name"><?php echo htmlspecialchars($name); ?></p>
    </div>
    <nav>
    <ul>
        <li>
        <a href="guard_homepage.php">Home</a>
        </li>
        <li>
            <a href="guard_myprofile.php">My Profile</a>
        </li>
         <li>    
            <a href="help_support_guard.php">Help & Support</a>
        </li>
        <li>
            <a href="../login.php" onclick="return confirm('Are you sure you want to log out?')">Log Out</a>
        </li>
    </ul>
</nav>
</div>
<style>
:root {
    --sidebar-width: 250px;
}

body {
    font-family: 'Roboto', Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f5f5f5;
    color: #333;
    min-height: 100vh;
    display: flex;
}

.sidebar {
    background-color: #009E60;
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
    text-align: center;
    padding: 20px 0;
}

.profile-image {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid #ecf0f1;
    object-fit: cover;
}

.dean-name {
    margin-top: 15px;
    font-size: 18px;
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
    border-left-color: orange;
}


/* Header Styles */
.header {
    background-color: #ff9042;
    width: calc(100% - var(--sidebar-width));
    padding: 0 20px;
    color: white;
    font-family: 'Georgia', serif;
    position: fixed;
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

/* Main Content Styles */
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
    color: white;
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
        margin: -75px -10px 20px;
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