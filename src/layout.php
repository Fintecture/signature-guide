<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Signature guide - Fintecture</title>
    <link rel="icon" href="assets/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" integrity="sha256-MBffSnbbXwHCuZtgPYiwMQbfE7z+GOZ7fBPCNB06Z98=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI=" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/prism.css">
</head>
<body>
    <main>
        <div class="container py-4">
            <header class="d-flex flex-wrap justify-content-center py-3 mb-4 border-bottom">
                <a href="<?php echo $hostPath; ?>" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-dark text-decoration-none">
                    <img id="logo" src="assets/logo.png" alt="Fintecture">
                </a>
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <a href="https://www.fintecture.com" class="nav-link"><i class="bi bi-box-arrow-up-right"></i> Fintecture.com</a>
                    </li>
                    <li class="nav-item">
                        <a href="https://docs.fintecture.com" class="nav-link"><i class="bi bi-box-arrow-up-right"></i> API Documentation</a>
                    </li>                    
                    <li class="nav-item">
                        <a href="https://github.com/Fintecture/signature-guide/tree/src/scripts" class="nav-link"><i class="bi bi-file-earmark-code"></i> Complete examples</a>
                    </li>
                </ul>
            </header>

            <?php
            if (isset($content)) {
                echo $content;
            }
                ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js" integrity="sha256-YMa+wAM6QkVyz999odX7lPRxkoYAan8suedu4k2Zur8=" crossorigin="anonymous"></script>
    <script src="assets/prism.js"></script>
    <script src="assets/main.js"></script>
</body>
</html>