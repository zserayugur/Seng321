<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Inter font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="container">

    <!-- HEADER -->
    <header class="main-header">
        <img 
  src="/Seng321/assets/logo2.png" 
  alt="LevelUP English"
  style="height: 120px; width: 130px;">
        <nav class="nav-links">
            <a href="home.php" class="active">Home</a>
            <a href="../login.php">Login</a>
            <a href="../register.php">Get Started</a>
        </nav>
    </header>

    <!-- HERO -->
    <div class="card">
        <h2>LevelUp Language Learning Platform</h2>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            Determine your CEFR level, receive personalized learning plans,
            and track your progress across all language skills.
        </p>

        <a href="../login_part/register.php" class="btn btn-primary">Get Started</a>
        <a href="../login_part/login.php" class="btn" style="margin-left:10px;">Login</a>
    </div>

    <!-- HOW IT WORKS -->
    <div class="dashboard-grid">

        <div class="card">
            <h2>Level Assessment</h2>
            <p>Determine your CEFR level (A1–C2) with a comprehensive test.</p>
        </div>

        <div class="card">
            <h2>Personalized Learning</h2>
            <p>AI-generated daily plans and recommended resources.</p>
        </div>

        <div class="card">
            <h2>Track Progress</h2>
            <p>Monitor improvement across all language skills.</p>
        </div>

    </div>

    <!-- FEATURES -->
    <div class="dashboard-grid">

        <div class="card">
            <h2>AI Smart Coach</h2>
            <p>Personalized learning insights.</p>
        </div>

        <div class="card">
            <h2>Skill-Based Tests</h2>
            <p>Reading, listening, writing, and speaking.</p>
        </div>

        <div class="card">
            <h2>Assignments & To-Do</h2>
            <p>Structured learning tasks.</p>
        </div>

        <div class="card">
            <h2>Progress Analytics</h2>
            <p>CEFR-aligned performance tracking.</p>
        </div>

    </div>

    <!-- CEFR -->
    <div class="card">
        <h2>CEFR Levels</h2>
        <p>
            <span class="badge badge-a1">A1</span>
            <span class="badge badge-a2">A2</span>
            <span class="badge badge-b1">B1</span>
            <span class="badge badge-b2">B2</span>
            <span class="badge badge-c1">C1</span>
            <span class="badge badge-c2">C2</span>
        </p>
    </div>
<footer style="text-align: center;">
    &copy; <?php echo date("Y"); ?> LevelUp English AI Learning Systems.
</footer>

</div>

</body>
</html>
