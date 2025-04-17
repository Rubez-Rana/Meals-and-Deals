<?php
session_start();
include("connect.php");

if(!isset($_SESSION['email'])) {
    header("location: index.php");
    exit();
}

$email = $_SESSION['email'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE email='".mysqli_real_escape_string($conn, $email)."'");

if(!$user_query) {
    die("Database query failed");
}

$user = mysqli_fetch_assoc($user_query);

if(!$user) {
    die("User not found");
}

// Get user posts
$posts_query = mysqli_query($conn, "SELECT * FROM posts WHERE user_id='".$user['Id']."' ORDER BY created_at DESC");
$posts = [];
if($posts_query) {
    while($row = mysqli_fetch_assoc($posts_query)) {
        $posts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - Meals and Deals</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .top-bar {
            background: linear-gradient(135deg, #ff9a9e 0%, #fad0c4 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .logo {
            font-family: 'Pacifico', cursive;
            font-size: 2rem;
            font-weight: 400;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .main-container {
            display: flex;
            flex: 1;
        }
        
        .sidebar {
            width: 250px;
            background-color: white;
            padding: 2rem 1rem;
            box-shadow: 2px 0 5px rgba(0,0,0,0.05);
            height: calc(100vh - 70px);
            position: sticky;
            top: 70px;
        }
        
        .sidebar-menu {
            list-style: none;
            margin-top: 2rem;
        }
        
        .sidebar-menu li {
            margin-bottom: 1.5rem;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .sidebar-menu a:hover, .sidebar-menu a.active {
            background-color: #f8f8f8;
            color: #ff6b6b;
        }
        
        .sidebar-menu i {
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .content {
            flex: 1;
            padding: 2rem;
        }
        
        /* Profile specific styles */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-pic {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #ff6b6b;
        }
        
        .profile-info h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .profile-info p {
            color: #777;
            margin-bottom: 1rem;
        }
        
        .profile-stats {
            display: flex;
            gap: 2rem;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: #ff6b6b;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #777;
        }
        
        .profile-bio {
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .profile-bio h3 {
            margin-bottom: 1rem;
            color: #333;
        }
        
        .profile-bio p {
            color: #555;
            line-height: 1.6;
        }
        
        /* Posts container */
        .posts-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        .post {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 1.5rem;
        }
        
        .post-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .post-user-img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .post-user-info h4 {
            font-weight: 600;
            margin-bottom: 0.2rem;
        }
        
        .post-user-info p {
            font-size: 0.8rem;
            color: #777;
        }
        
        .post-content {
            margin-bottom: 1rem;
        }
        
        .post-image {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .post-actions {
            display: flex;
            gap: 1rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }
        
        .post-action {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #777;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .post-action:hover {
            color: #ff6b6b;
        }
        
        .logout-btn {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .logout-btn:hover {
            text-decoration: underline;
        }
        
        .edit-profile-btn {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 1rem;
        }
        
        .edit-profile-btn:hover {
            background-color: #ff5252;
        }


        .posts-container {
            gap: 2rem; 
        }

        .post {
            margin-bottom: 2rem; 
        }


        .post-form {
            margin-bottom: 2rem; 
        }


        .content {
            padding: 2rem;
            padding-top: 1rem; 
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="logo">Meals and Deals</div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($user['firstName']); ?></span>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['firstName'].'+'.$user['lastName']); ?>&background=ff6b6b&color=fff" alt="Profile">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
    
    <div class="main-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="homepage.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Your Profile</a></li>
                <li><a href="#"><i class="fas fa-cog"></i> Settings</a></li>
            </ul>
        </div>
        
        <div class="content">
            <div class="profile-header">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['firstName'].'+'.$user['lastName']); ?>&background=ff6b6b&color=fff&size=150" class="profile-pic">
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['firstName'].' '.$user['lastName']); ?></h2>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="profile-stats">
                        <div class="stat">
                            <div class="stat-value"><?php echo count($posts); ?></div>
                            <div class="stat-label">Posts</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Followers</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Following</div>
                        </div>
                    </div>
                    <button class="edit-profile-btn">Edit Profile</button>
                </div>
            </div>
            
            <div class="profile-bio">
                <h3>About</h3>
                <p><?php echo !empty($user['bio']) ? nl2br(htmlspecialchars($user['bio'])) : 'No bio yet.'; ?></p>
            </div>
            
            <h3 style="margin-bottom: 1rem;">Your Posts</h3>
            
            <div class="posts-container">
                <?php if(empty($posts)): ?>
                    <div class="post">
                        <p>You haven't posted anything yet.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($posts as $post): ?>
                        <div class="post">
                            <div class="post-header">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['firstName'].'+'.$user['lastName']); ?>&background=ff6b6b&color=fff" class="post-user-img">
                                <div class="post-user-info">
                                    <h4><?php echo htmlspecialchars($user['firstName'].' '.$user['lastName']); ?></h4>
                                    <p><?php echo date('F j, Y \a\t g:i a', strtotime($post['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            </div>
                            <?php if(!empty($post['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" class="post-image">
                            <?php endif; ?>
                            </div>
                            <div class="post-actions">
                                <div class="post-action">
                                    <i class="far fa-heart"></i>
                                    <span><?php echo $post['likes_count'] ?? 0; ?></span>
                                </div>
                                <div class="post-action">
                                    <i class="far fa-comment"></i>
                                    <span><?php echo $post['comments_count'] ?? 0; ?></span>
                                </div>
                                <div class="post-action">
                                    <i class="fas fa-share"></i>
                                    <span>Share</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>