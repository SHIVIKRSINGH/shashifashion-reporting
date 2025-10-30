<?php
// Enable error reporting (development only)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Central DB config
$host = 'localhost';
$user = 'shivendra';
$password = '24199319@Shiv';
$central_db = 'softgen_db_central';

// Connect to central DB
$con = new mysqli($host, $user, $password, $central_db);
if ($con->connect_error) {
    die("❌ Central DB connection failed: " . $con->connect_error);
}
$con->query("SET time_zone = '+05:30'");
$con->set_charset("utf8mb4");

// -------------------------------
// Branch DB Logic
// -------------------------------
$branch_con = null;  // Global branch connection

if (isset($_SESSION['branch_db'])) {
    // 🟢 Branch DB connection exists in session
    $dbConf = $_SESSION['branch_db'];
    $branch_con = new mysqli($dbConf['host'], $dbConf['user'], $dbConf['password'], $dbConf['name']);

    if ($branch_con->connect_error) {
        die("❌ Branch DB connection failed: " . $branch_con->connect_error);
    }

    $branch_con->query("SET time_zone = '+05:30'");
    $branch_con->set_charset("utf8mb4");
} elseif (isset($_SESSION['role_name'])) {
    // 🟡 No branch_db in session, but role is known
    $role = strtolower($_SESSION['role_name']);
    $branch_id = $_SESSION['branch_id'] ?? null;

    if ($role === 'admin') {
        // 🔹 Admin: Load all branches & set first one as default
        $branches = [];
        $res = $con->query("SELECT * FROM m_branch_sync_config ORDER BY branch_id");
        while ($row = $res->fetch_assoc()) {
            $branches[] = $row;
        }

        if (!empty($branches)) {
            $_SESSION['all_branches'] = $branches;

            // Set first branch as default
            $default = $branches[0];
            $_SESSION['branch_db'] = [
                'host'     => $default['db_host'],
                'user'     => $default['db_user'],
                'password' => $default['db_password'],
                'name'     => $default['db_name']
            ];
            $_SESSION['branch_id'] = $default['branch_id'];

            // Connect to it
            $branch_con = new mysqli(
                $default['db_host'],
                $default['db_user'],
                $default['db_password'],
                $default['db_name']
            );

            if ($branch_con->connect_error) {
                die("❌ Admin default branch DB failed: " . $branch_con->connect_error);
            }

            $branch_con->query("SET time_zone = '+05:30'");
            $branch_con->set_charset("utf8mb4");
        } else {
            die("❌ No branches found in config for admin.");
        }
    } elseif ($role === 'manager' && !empty($branch_id)) {
        // 🔸 Manager: Load dedicated branch
        $stmt = $con->prepare("SELECT * FROM m_branch_sync_config WHERE branch_id = ?");
        $stmt->bind_param("s", $branch_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $_SESSION['branch_db'] = [
                'host'     => $row['db_host'],
                'user'     => $row['db_user'],
                'password' => $row['db_password'],
                'name'     => $row['db_name']
            ];

            $branch_con = new mysqli(
                $row['db_host'],
                $row['db_user'],
                $row['db_password'],
                $row['db_name']
            );

            if ($branch_con->connect_error) {
                die("❌ Manager branch DB failed: " . $branch_con->connect_error);
            }

            $branch_con->query("SET time_zone = '+05:30'");
            $branch_con->set_charset("utf8mb4");
        } else {
            die("❌ Branch config not found for manager.");
        }
    }
}

// ---------------------------------------------------
// ✅ ATTENDANCE CONFIG SECTION (GeoLocation Only)
// ---------------------------------------------------
define('ATTENDANCE_ENABLED', true);
define('DEFAULT_ALLOWED_RADIUS', 0.2); // 0.2 km = 200 meters

/**
 * Calculate distance (in km) between two GPS points
 */
function calculate_distance_km($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

/**
 * Check if employee location is within allowed radius
 */
function is_within_allowed_radius($emp_lat, $emp_lon, $office_lat, $office_lon, $allowed_radius_km = DEFAULT_ALLOWED_RADIUS)
{
    if (empty($emp_lat) || empty($emp_lon) || empty($office_lat) || empty($office_lon)) {
        return false;
    }
    $distance = calculate_distance_km($emp_lat, $emp_lon, $office_lat, $office_lon);
    return ($distance <= $allowed_radius_km);
}
