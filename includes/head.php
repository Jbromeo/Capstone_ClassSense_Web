<?php
// includes/head.php
require_once dirname(__DIR__) . '/core/init.php'; 

// Dynamically handle asset paths based on folder depth
$pathPrefix = (strpos($_SERVER['SCRIPT_NAME'], 'admin_screen') !== false || 
               strpos($_SERVER['SCRIPT_NAME'], 'student_screen') !== false || 
               strpos($_SERVER['SCRIPT_NAME'], 'teacher_screen') !== false) ? '../' : '';
?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/png" href="<?php echo $pathPrefix; ?>assets/classsense-logo.png">
<link rel="stylesheet" href="<?php echo $pathPrefix; ?>style.css">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/feather-icons"></script>
<script>
    // Global Configuration for JavaScript stability across folders
    window.CS_ROOT = '<?php echo ROOT_URL; ?>';
    
    tailwind.config = {
        darkMode: 'class',
        theme: {
            extend: {
                colors: {
                    primary: { DEFAULT: '#ea2628', 50: '#fef2f2', 100: '#fee2e2', 500: '#ea2628', 600: '#dc2626', 700: '#b91c1c', 900: '#7f1d1d' },
                    secondary: { 500: '#9d8989', 600: '#826a6a' },
                    dark: { bg: '#0f1115', surface: '#181b21', border: '#2a2e35' }
                },
                fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] }
            }
        }
    };
</script>

<!-- 🛡️ Global Identity Orchestrator: Syncs PHP Session with Firebase Auth project-wide -->
<script type="module" src="<?php echo $pathPrefix; ?>assets/js/auth_controller.js?v=<?php echo time(); ?>"></script>
