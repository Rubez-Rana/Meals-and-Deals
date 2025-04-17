<?php
session_start();
include("connect.php");
include ("config.php");

// Redirect if not logged in
if (!isset($_SESSION['email'])) {
    header("location: index.php");
    exit();
}

// Get current user info
$email = $_SESSION['email'];
$current_user_query = mysqli_query($conn, "SELECT * FROM users WHERE email='".mysqli_real_escape_string($conn, $email)."'");
$current_user = mysqli_fetch_assoc($current_user_query);

// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $user_id = $current_user['Id'];
    $recipe_instructions = mysqli_real_escape_string($conn, $_POST['recipe_instructions']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    
    // Handle ingredients
    $ingredients = [];
    if (!empty($_POST['ingredients'])) {
        $ingredients = json_decode($_POST['ingredients'], true);
        if (!is_array($ingredients)) {
            $ingredients = [];
        }
    }
    $ingredients_json = mysqli_real_escape_string($conn, json_encode($ingredients));
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_ext, $allowed_types)) {
            $file_name = uniqid('post_', true) . '.' . $file_ext;
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_path)) {
                $image_path = $target_path;
            }
        }
    }
    
    // Insert post into database
    $query = "INSERT INTO posts (user_id, title, content, image_path, recipe_instructions, ingredients) 
          VALUES ('$user_id', '$title', '$content', '$image_path', '$recipe_instructions', '$ingredients_json')";
    mysqli_query($conn, $query);
    header("Location: homepage.php");
    exit();
}

// Handle like/unlike
if (isset($_GET['like_post'])) {
    $post_id = intval($_GET['like_post']);
    $user_id = $current_user['Id'];
    
    // Check if already liked
    $check_like = mysqli_query($conn, "SELECT * FROM likes WHERE user_id='$user_id' AND post_id='$post_id'");
    
    if (mysqli_num_rows($check_like) > 0) {
        // Unlike
        mysqli_query($conn, "DELETE FROM likes WHERE user_id='$user_id' AND post_id='$post_id'");
        mysqli_query($conn, "UPDATE posts SET likes_count = likes_count - 1 WHERE id='$post_id'");
    } else {
        // Like
        mysqli_query($conn, "INSERT INTO likes (user_id, post_id) VALUES ('$user_id', '$post_id')");
        mysqli_query($conn, "UPDATE posts SET likes_count = likes_count + 1 WHERE id='$post_id'");
    }
    
    header("Location: homepage.php");
    exit();
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $post_id = intval($_POST['post_id']);
    $content = mysqli_real_escape_string($conn, $_POST['comment_content']);
    $user_id = $current_user['Id'];
    
    mysqli_query($conn, "INSERT INTO comments (user_id, post_id, content) VALUES ('$user_id', '$post_id', '$content')");
    mysqli_query($conn, "UPDATE posts SET comments_count = comments_count + 1 WHERE id='$post_id'");
    
    header("Location: homepage.php");
    exit();
}

// Handle post deletion
if (isset($_GET['delete_post'])) {
    $post_id = intval($_GET['delete_post']);
    $user_id = $current_user['Id'];
    
    // Verify the post belongs to the current user
    $check_post = mysqli_query($conn, "SELECT * FROM posts WHERE id='$post_id' AND user_id='$user_id'");
    
    if (mysqli_num_rows($check_post) > 0) {
        // Delete associated likes and comments first
        mysqli_query($conn, "DELETE FROM likes WHERE post_id='$post_id'");
        mysqli_query($conn, "DELETE FROM comments WHERE post_id='$post_id'");
        
        // Delete the post
        mysqli_query($conn, "DELETE FROM posts WHERE id='$post_id'");
    }
    
    header("Location: homepage.php");
    exit();
}

// Handle post editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    $post_id = intval($_POST['post_id']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);
    $user_id = $current_user['Id'];
    
    // Verify the post belongs to the current user
    $check_post = mysqli_query($conn, "SELECT * FROM posts WHERE id='$post_id' AND user_id='$user_id'");
    
    if (mysqli_num_rows($check_post) > 0) {
        // Handle image upload if a new one is provided
        $image_path = $_POST['existing_image'];
        if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_ext = strtolower(pathinfo($_FILES['post_image']['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($file_ext, $allowed_types)) {
                $file_name = uniqid('post_', true) . '.' . $file_ext;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['post_image']['tmp_name'], $target_path)) {
                    // Delete old image if it exists
                    if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                        unlink($_POST['existing_image']);
                    }
                    $image_path = $target_path;
                }
            }
        }
        
        // Update post in database
        $query = "UPDATE posts SET content='$content', image_path='$image_path' WHERE id='$post_id'";
        mysqli_query($conn, $query);
    }

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] !== UPLOAD_ERR_OK && $_FILES['post_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $error_message = "Image upload failed: ";
        switch ($_FILES['post_image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message .= "File too large";
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message .= "Upload incomplete";
                break;
            default:
                $error_message .= "Unknown error";
        }
        echo "<script>alert('$error_message');</script>";
    }
    
    header("Location: homepage.php");
    exit();
}

// Get all posts with user info and like status
$posts_query = mysqli_query($conn, "
    SELECT posts.*, 
           users.firstName, 
           users.lastName,
           (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id AND likes.user_id = '{$current_user['Id']}') as is_liked
    FROM posts
    JOIN users ON posts.user_id = users.Id
    ORDER BY posts.created_at DESC
");

$posts = [];
while ($row = mysqli_fetch_assoc($posts_query)) {
    $posts[] = $row;
}

// Function to get comments for a post
function getComments($conn, $post_id) {
    $comments_query = mysqli_query($conn, "
        SELECT comments.*, users.firstName, users.lastName
        FROM comments
        JOIN users ON comments.user_id = users.Id
        WHERE comments.post_id = '$post_id'
        ORDER BY comments.created_at ASC
    ");
    
    $comments = [];
    while ($row = mysqli_fetch_assoc($comments_query)) {
        $comments[] = $row;
    }
    return $comments;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HomePage - Meals and Deals</title>
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

        .search-container {
            position: relative;
            margin-bottom: 1rem;
        }

        .suggestions-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background: white;
            border: 1px solid #ddd;
            border-radius: 0 0 8px 8px;
            z-index: 100;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .suggestion-item {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }

        .suggestion-item:hover {
            background-color: #f5f5f5;
        }

        .selected-ingredients {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .post-title {
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
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
        
        .welcome-message {
            margin-bottom: 2rem;
            font-size: 1.5rem;
            color: #333;
        }
        
        .post-form {
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        
        .post-form textarea {
            width: 100%;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            resize: none;
            margin-bottom: 1rem;
            font-family: inherit;
        }
        
        .post-form button {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .post-form button:hover {
            background-color: #ff5252;
        }
        
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
            margin-bottom: 2rem; /* This ensures space between posts */
            position: relative; /* Helps with child element positioning */
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
        
        .post-action.liked {
            color: #ff6b6b;
        }
        
        .post-action.liked i.far.fa-heart {
            display: none;
        }
        
        .post-action.liked i.fas.fa-heart {
            display: inline;
        }
        
        .post-action i.fas.fa-heart {
            display: none;
            color: #ff6b6b;
        }
        
        .comments-section {
            margin-top: 1rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }
        
        .comment {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .comment-user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .comment-content {
            flex: 1;
            background-color: #f8f8f8;
            padding: 0.8rem;
            border-radius: 8px;
        }
        
        .comment-user-name {
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }
        
        .comment-text {
            font-size: 0.9rem;
        }
        
        .comment-time {
            font-size: 0.7rem;
            color: #777;
            margin-top: 0.3rem;
        }
        
        .add-comment {
            display: flex;
            margin-top: 1rem;
            gap: 1rem;
        }
        
        .add-comment input {
            flex: 1;
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 20px;
            outline: none;
        }
        
        .add-comment button {
            background-color: #ff6b6b;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            cursor: pointer;
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
        
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-bottom: 1rem;
            display: none;
        }

        /* Responsive images */
        .post-image {
            max-width: 100%;
            height: auto;
            object-fit: contain;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
        }

        .close-modal {
            float: right;
            cursor: pointer;
            font-size: 1.5rem;
        }

        /* Post actions menu */
        .post-actions-menu {
            margin-left: auto;
        }

        .ingredients-section {
        margin-bottom: 1rem;
    }
    
    #ingredient-search {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        border-radius: 20px;
        margin-bottom: 1rem;
    }
    
    #selected-ingredients {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    
    .ingredient-tag {
        background-color: #ff6b6b;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        display: flex;
        align-items: center;
        cursor: pointer;
    }
    
    .ingredient-tag:hover {
        background-color: #ff5252;
    }
    
    .remove-ingredient {
        background: none;
        border: none;
        color: white;
        margin-left: 0.5rem;
        cursor: pointer;
    }
    
    .nearby-stores {
        background-color: white;
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .store {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eee;
    }
    
    /* Modal styles */
    #ingredient-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }
    
    #ingredient-modal-content {
        background-color: white;
        padding: 2rem;
        border-radius: 10px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .products-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1rem;
        margin-top: 1rem;
    }
    
    .product img {
        max-width: 100%;
        height: auto;
    }

        .ingredient-tag {
            background-color: #ff6b6b;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            display: inline-flex; /* Changed from flex to inline-flex */
            align-items: center;
            cursor: pointer;
            margin: 0.2rem; /* Added some margin */
            font-size: inherit; /* Ensure it inherits parent font size */
            line-height: normal; /* Reset line height */
            white-space: nowrap; /* Prevent text wrapping */
        }

        .ingredient-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1001;
            align-items: center;
            justify-content: center;
        }

        .ingredient-modal-content {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .ingredient-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .ingredient-modal-title {
            font-size: 1.5rem;
            color: #ff6b6b;
        }

        .ingredient-modal-close {
            font-size: 1.5rem;
            cursor: pointer;
        }

        .ingredient-modal-body {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .map-container {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
        }

        .products-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1rem;
        }

        .product-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .product-brand {
            color: #777;
            font-size: 0.9rem;
        }

        .map-container {
            height: 300px;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1rem;
            border: 1px solid #eee;
        }

        .product-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }

        .product-card img {
            max-width: 100%;
            max-height: 100px;
            object-fit: contain;
            margin-bottom: 0.5rem;
        }

        .product-title {
            font-weight: bold;
            margin-bottom: 0.2rem;
        }

        .product-brand {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 0.2rem;
        }

        .product-price {
            color: #ff6b6b;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="logo">Meals and Deals</div>
        <div class="user-info">
            <span><?php echo htmlspecialchars($current_user['firstName']); ?></span>
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($current_user['firstName'].'+'.$current_user['lastName']); ?>&background=ff6b6b&color=fff" alt="Profile">
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </div>
    
    <div class="main-container">
        <div class="sidebar">
            <ul class="sidebar-menu">
                <li><a href="homepage.php" class="active"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Your Profile</a></li>
            </ul>
        </div>
        
        <div class="content">
            <div class="welcome-message">
                Welcome back, <?php echo htmlspecialchars($current_user['firstName']); ?>!
            </div>
            
            <div class="post-form">
                <form method="POST" action="homepage.php" enctype="multipart/form-data">
                    <!-- Title field (bold text) -->
                    <input type="text" name="title" placeholder="Post Title (e.g. 'My Famous Spaghetti Bolognese')" required 
                        style="font-weight: bold; width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; font-family: inherit;">
                    
                    <textarea name="content" placeholder="Describe your recipe or food experience..." required></textarea>
                    
                    <div class="ingredients-section">
                        <h4>Ingredients</h4>
                        <div class="search-container">
                            <input type="text" id="ingredient-search" placeholder="Search ingredients..." autocomplete="off">
                            <div id="ingredient-suggestions" class="suggestions-dropdown"></div>
                        </div>
                        <div id="selected-ingredients" class="selected-ingredients"></div>
                        <input type="hidden" name="ingredients" id="ingredients-json">
                    </div>
                    
                    <textarea name="recipe_instructions" placeholder="Recipe instructions (step by step)..." required></textarea>
                    
                    <img id="image-preview" class="image-preview" src="#" alt="Preview">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <button type="button" style="background: transparent; color: #777; padding: 0;" onclick="document.getElementById('image-upload').click()">
                                <i class="fas fa-image"></i> Add Photo
                            </button>
                            <input type="file" id="image-upload" name="post_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        </div>
                        <button type="submit" name="create_post">Post</button>
                    </div>
                </form>
            </div>

            <div class="nearby-stores" id="nearby-stores">
                <!-- Stores will be loaded here by JavaScript -->
            </div>

            <!-- Ingredient Availability Modal -->
            <div id="ingredient-modal">
                <div id="ingredient-modal-content">
                    <span class="close-modal">&times;</span>
                    <!-- Content will be loaded here by JavaScript -->
                </div>
            </div>
            
            <div class="posts-container">
                <?php if(empty($posts)): ?>
                    <div class="post">
                        <p>No posts yet. Be the first to share!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($posts as $post): 
                        $comments = getComments($conn, $post['id']);
                    ?>
                        <div class="post">
                            <div class="post-header">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($post['firstName'].'+'.$post['lastName']); ?>&background=ff6b6b&color=fff" class="post-user-img">
                                <div class="post-user-info">
                                    <h4><?php echo htmlspecialchars($post['firstName'].' '.$post['lastName']); ?></h4>
                                    <p>
                                        <?php echo date('F j, Y \a\t g:i a', strtotime($post['created_at'])); ?>
                                        <?php if ($post['created_at'] != $post['updated_at']): ?>
                                            <br><small>(edited)</small>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <?php if ($post['user_id'] == $current_user['Id']): ?>
                                <div class="post-actions-menu" style="margin-left: auto;">
                                    <button class="edit-post-btn" data-post-id="<?php echo $post['id']; ?>" data-content="<?php echo htmlspecialchars($post['content']); ?>" data-image="<?php echo htmlspecialchars($post['image_path']); ?>" style="background: none; border: none; cursor: pointer; color: #777;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete_post=<?php echo $post['id']; ?>" onclick="return confirm('Are you sure you want to delete this post?');" style="color: #777; margin-left: 0.5rem;">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <div class="post-title"><?php echo htmlspecialchars($post['title']); ?></div>
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                
                                <?php if (!empty($post['recipe_instructions'])): ?>
                                <div class="recipe-instructions">
                                    <h4>Instructions:</h4>
                                    <p><?php echo nl2br(htmlspecialchars($post['recipe_instructions'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($post['ingredients'])): 
                                    $ingredients = json_decode($post['ingredients'], true);
                                    if (is_array($ingredients) && !empty($ingredients)): ?>
                                    <div class="post-ingredients">
                                        <h4>Ingredients:</h4>
                                        <div class="ingredients-list">
                                            <?php foreach ($ingredients as $ingredient): ?>
                                                <?php if (!empty(trim($ingredient))): ?>
                                                    <span class="ingredient-tag" onclick="checkIngredientAvailability('<?php echo htmlspecialchars($ingredient); ?>')">
                                                        <?php echo htmlspecialchars($ingredient); ?>
                                                    </span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if (!empty($post['image_path']) && file_exists($post['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" class="post-image">
                                <?php endif; ?>

                            <div class="post-actions">
                                <a href="?like_post=<?php echo $post['id']; ?>" class="post-action <?php echo $post['is_liked'] ? 'liked' : ''; ?>">
                                    <i class="far fa-heart"></i>
                                    <i class="fas fa-heart"></i>
                                    <span><?php echo $post['likes_count']; ?></span>
                                </a>
                                <div class="post-action">
                                    <i class="far fa-comment"></i>
                                    <span><?php echo $post['comments_count']; ?></span>
                                </div>
                                <div class="post-action">
                                    <i class="fas fa-share"></i>
                                    <span>Share</span>
                                </div>
                            </div>
                            
                            <div class="comments-section">
                                <?php foreach($comments as $comment): ?>
                                    <div class="comment">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($comment['firstName'].'+'.$comment['lastName']); ?>&background=ff6b6b&color=fff" class="comment-user-img">
                                        <div class="comment-content">
                                            <div class="comment-user-name"><?php echo htmlspecialchars($comment['firstName'].' '.$comment['lastName']); ?></div>
                                            <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
                                            <div class="comment-time"><?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <form method="POST" class="add-comment">
                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                    <input type="text" name="comment_content" placeholder="Write a comment..." required>
                                    <button type="submit" name="add_comment">Post</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

        <!-- Edit Post Modal -->
    <div id="editPostModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="modal-content" style="background-color: white; padding: 2rem; border-radius: 10px; max-width: 600px; width: 90%;">
            <span class="close-modal" style="float: right; cursor: pointer; font-size: 1.5rem;">&times;</span>
            <h3>Edit Post</h3>
            <form id="editPostForm" method="POST" action="homepage.php" enctype="multipart/form-data">
                <input type="hidden" name="post_id" id="edit_post_id">
                <input type="hidden" name="existing_image" id="existing_image">
                <textarea name="content" id="edit_post_content" style="width: 100%; border: 1px solid #ddd; border-radius: 8px; padding: 1rem; resize: none; margin-bottom: 1rem; font-family: inherit; min-height: 150px;"></textarea>
                <img id="edit_image_preview" class="image-preview" src="#" alt="Preview" style="max-width: 100%; max-height: 200px; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <button type="button" style="background: transparent; color: #777; padding: 0;" onclick="document.getElementById('edit_image_upload').click()">
                            <i class="fas fa-image"></i> Change Photo
                        </button>
                        <button type="button" style="background: transparent; color: #ff6b6b; padding: 0; margin-left: 1rem;" id="remove_image_btn">
                            <i class="fas fa-trash"></i> Remove Photo
                        </button>
                        <input type="file" id="edit_image_upload" name="post_image" accept="image/*" style="display: none;" onchange="previewEditImage(this)">
                    </div>
                    <button type="submit" name="update_post">Update</button>
                </div>
            </form>
        </div>
    </div>
    

    <div id="ingredientModal" class="ingredient-modal">
        <div class="ingredient-modal-content">
            <div class="ingredient-modal-header">
                <h3 class="ingredient-modal-title" id="ingredientModalTitle"></h3>
                <span class="ingredient-modal-close">&times;</span>
            </div>
            <div class="ingredient-modal-body">
                <h4>Nearby Stores</h4>
                <div class="map-container" id="ingredientMap"></div>
                <h4>Available Products</h4>
                <div class="products-container" id="ingredientProducts"></div>
            </div>
        </div>
    </div>

    
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo GOOGLE_PLACES_API_KEY; ?>&libraries=places"></script>
    <script src="geolocation.js"></script>
    <script>
        async function checkIngredientAvailability(ingredient) {
            const modal = document.getElementById('ingredientModal');
            const modalTitle = document.getElementById('ingredientModalTitle');
            const productsContainer = document.getElementById('ingredientProducts');
            const mapContainer = document.getElementById('ingredientMap');
            
            // Set modal title
            modalTitle.textContent = `Availability for: ${ingredient}`;
            
            // Clear previous content
            productsContainer.innerHTML = '<p>Loading products...</p>';
            mapContainer.innerHTML = '<p>Loading map...</p>';
            
            // Show modal
            modal.style.display = 'flex';
            
            try {
                // Get product data using our search endpoint
                const response = await fetch(`search.php?query=${encodeURIComponent(ingredient)}`);
                const data = await response.json();
                
                productsContainer.innerHTML = '';
                
                if (data.products && data.products.length > 0) {
                    data.products.forEach(product => {
                        const productCard = document.createElement('div');
                        productCard.className = 'product-card';
                        
                        productCard.innerHTML = `
                            ${product.image ? `<img src="${product.image}" alt="${product.title}">` : ''}
                            <div class="product-title">${product.title}</div>
                            <div class="product-brand">${product.brand || 'Unknown brand'}</div>
                            ${product.price ? `<div class="product-price">$${product.price}</div>` : ''}
                        `;
                        productsContainer.appendChild(productCard);
                    });
                } else {
                    productsContainer.innerHTML = '<p>No products found for this ingredient.</p>';
                }

                // Initialize map if Google API is available
                if (typeof google !== 'undefined') {
                    initIngredientMap(ingredient);
                } else {
                    mapContainer.innerHTML = '<p>Map functionality not available</p>';
                }
            } catch (error) {
                console.error('Error:', error);
                productsContainer.innerHTML = '<p>Error loading product information.</p>';
                mapContainer.innerHTML = '<p>Error loading map.</p>';
            }
        }

        // Function to initialize map for ingredient
        function initIngredientMap(ingredient) {
            const mapContainer = document.getElementById('ingredientMap');
            
            if (!navigator.geolocation) {
                mapContainer.innerHTML = '<p>Geolocation is not supported by your browser.</p>';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                position => {
                    const userLat = position.coords.latitude;
                    const userLng = position.coords.longitude;
                    
                    try {
                        const map = new google.maps.Map(mapContainer, {
                            center: {lat: userLat, lng: userLng},
                            zoom: 13
                        });
                        
                        // Add user marker
                        new google.maps.Marker({
                            position: {lat: userLat, lng: userLng},
                            map: map,
                            title: 'Your Location',
                            icon: {
                                url: 'https://maps.google.com/mapfiles/ms/icons/blue-dot.png'
                            }
                        });
                        
                        // Search for nearby grocery stores
                        const service = new google.maps.places.PlacesService(map);
                        service.nearbySearch({
                            location: {lat: userLat, lng: userLng},
                            radius: 5000,
                            type: ['grocery_or_supermarket']
                        }, (results, status) => {
                            if (status === google.maps.places.PlacesServiceStatus.OK) {
                                results.slice(0, 5).forEach(place => {
                                    new google.maps.Marker({
                                        position: place.geometry.location,
                                        map: map,
                                        title: place.name
                                    });
                                });
                            } else {
                                mapContainer.innerHTML += '<p>Could not load nearby stores</p>';
                            }
                        });
                    } catch (error) {
                        console.error('Google Maps error:', error);
                        mapContainer.innerHTML = '<p>Error loading map. Please try again later.</p>';
                    }
                },
                error => {
                    console.error('Geolocation error:', error);
                    mapContainer.innerHTML = '<p>Could not determine your location. Please enable location services.</p>';
                }
            );
        }

        // Close modal when clicking X
        document.querySelector('.ingredient-modal-close').addEventListener('click', function() {
            document.getElementById('ingredientModal').style.display = 'none';
        });

        // Close modal when clicking outside content
        document.getElementById('ingredientModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });

        // Rest of your existing JavaScript...
        document.querySelector('.post-form form').addEventListener('submit', function(e) {
            const ingredients = [];
            document.querySelectorAll('#selected-ingredients .ingredient-tag span').forEach(span => {
                ingredients.push(span.textContent.trim());
            });
            document.getElementById('ingredients-json').value = JSON.stringify(ingredients);
            
            if (ingredients.length === 0) {
                e.preventDefault();
                alert('Please add at least one ingredient');
                return;
            }
        });

        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const file = input.files[0];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            if (file) {
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>