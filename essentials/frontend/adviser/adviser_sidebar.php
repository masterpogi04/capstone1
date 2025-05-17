<?php
// Ensure the user is logged in and is an adviser
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'adviser') {
    header("Location: ../login.php");
    exit();
}

// Get the adviser's ID and name from the session
$adviser_id = $_SESSION['user_id'];
$adviser_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Adviser';

// Define UPLOADS_URL if not already defined
if (!defined('UPLOADS_URL')) {
    define('UPLOADS_URL', '/capstone1/uploads/adviser_profiles/'); // Adjust based on your file path
}

// Get the profile picture path from the session
$profile_picture = isset($_SESSION['profile_picture']) ? $_SESSION['profile_picture'] : '';

// If the profile picture is not set or doesn't exist, use a default image
$profile_picture_url = !empty($profile_picture) && file_exists($_SERVER['DOCUMENT_ROOT'] . UPLOADS_URL . basename($profile_picture))
    ? UPLOADS_URL . basename($profile_picture)
    : '/path/to/default/profile/image.jpg';
?>

<div class="sidebar">
    <div class="profile-section text-center">
        <img src="<?php echo htmlspecialchars($profile_picture_url); ?>" class="profile-image" alt="Profile Picture">
        <h4 class="adviser-name"><?php echo htmlspecialchars($adviser_name); ?></h4>
    </div>
    <nav>
        <ul>
            <li><a href="adviser_homepage.php"> Home</a></li>
            <li><a href="adviser_dashboard.php"> Dashboard</a></li>
            <li><a href="adviser_my_profile.php">My Profile</a></li>
            <li><a href="help_support.php"> Help & Support</a></li>
            <li><a href="../login.php" onclick="return confirmLogout()"> Log Out</a></li>
        </ul>
    </nav>
</div>

<style>

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
    background-color: #00563B;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    width: 250px;
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

.adviser-name {
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
    background-color: #2c3e50;
    border-left-color: orange;
}

.header {
    background-color: #ff9042;
    width: calc(100% - 250px); /* Full width minus sidebar width */
    padding: 10px;
    color: white;
    font-family: 'Georgia', serif;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 250px;
    z-index: 1000;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
.header h1{
    margin: 0;
    font-size: 2rem;
    font-weight: 400;
}

.main-content {
    margin-left: 250px;
    padding: 80px 20px 70px;
    flex: 1;
    min-height: calc(100vh - 130px);
    box-sizing: border-box;
}

.footer {
    background-color: #ff9042;
    color: #ecf0f1;
    text-align: center;
    padding: 15px;
    position: fixed;
    bottom: 0;
    left: 250px;
    right: 0;
    height: 50px;
    z-index: 1000;
}

@media (max-width: 768px) {
    body {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        padding-top: 0;
    }

    .header {
        width: 100%;
        left: 0;
        top: auto;
        position: relative;
    }

    .main-content {
        margin-left: 0;
        padding-top: 20px;
    }

    .footer {
        left: 0;
        position: relative;
    }
}
</style>

<script>
    function confirmLogout() {
        return confirm('Are you sure you want to log out?');
    }
</script>
