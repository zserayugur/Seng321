<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Join Class</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<div class="container center">
    <div class="card">
        <h2 class="card-title">Join a Class</h2>
        <p class="text-muted">
            Enter the class code provided by your instructor.
        </p>

        <form method="post" action="join_class_action.php" class="form">
            <div class="form-group">
                <input
                    type="text"
                    name="class_code"
                    class="input"
                    placeholder="Class Code (e.g. ENG-7F3K9A)"
                    required
                >
            </div>
            <button type="submit" class="btn btn-primary full-width">
                Send Join Request
            </button>
        </form>
    </div>
</div>

</body>
</html>
