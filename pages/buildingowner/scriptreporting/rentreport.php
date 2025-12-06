<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../css/styledashowner.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <div class="side-bar collapsed">
        <a href="" class="logo">
            <img src="" alt="" class="logo-img">
            <img src="" alt="" class="logo-icon">
        </a>
        <ul class="nav-link">
            <li><a href="../ownerdashboard.html"><i class="fas fa-th-large"></i>
                    <p>DashBoard</p>
                </a></li>
            <li><a href="../tenantmanage.php"><i class="fas fa-users"></i>
                    <p>User Access Management</p>
                </a></li>
            <li><a href="#"><i class="fas fa-user-check"></i>
                    <p>Room Management</p>
                </a></li>
            <li><a href="../contractmanage.php"><i class="fas fa-file-contract"></i>
                    <p>Maintenance Complaint</p>
                </a></li>
            <li><a href="../rules.php"><i class="fas fa-file-lines"></i>
                    <p>Report Management</p>
                </a></li>
            <li><a href="rentreport.php"><i class="fas fa-cog"></i>
                    <p>User Account</p>
                </a></li>
            <div class="active"></div>
        </ul>
    </div>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>Rent Collection<br>
                    Report</h1>
            </div>
            <div class="topbar-right">
                <div class="search-box">
                    <input type="text" placeholder="Search">
                </div>
                <div class="user-info">
                    <span>Adrian Fernando</span>
                    <img src="" alt="User">
                </div>
            </div>
        </div>

        <div class="cards">
            <div class="card" style="background-color: #ffe5e5;">
                <i class="fas fa-users"></i>
                <?php
                $sql1 = 'SELECT COUNT(*) AS total_tenant FROM user WHERE role = "Tenant"';
                $rs1 = mysqli_query($db_connection, $sql1);
                $rw1 = mysqli_fetch_assoc($rs1);
                ?>
                <h3><?php echo $rw1['total_tenant']; ?></h3>

                <p>Total Tenants</p>
            </div>
            <div class="card" style="background-color: #fff3cd;">
                <i class="fas fa-briefcase"></i>
                <?php
                $sql = 'SELECT paymentontime, paymentlatetime FROM payment_summary';
                $rs = mysqli_query($db_connection, $sql);
                $rw = mysqli_fetch_assoc($rs);
                ?>
                <h3><?php echo $rw['paymentontime']; ?></< /h3>
                    <p>Payments on Time</p>
            </div>
            <div class="card" style="background-color: #e0f7fa;">
                <i class="fas fa-user-lock"></i>

                <h3><?php echo $rw['paymentlatetime']; ?></h3>
                <p>Late Payments</p>
            </div>
        </div>
        <div class="report-content">
            <div class="report-top">


                <div class="chart">
                    <canvas id="myChart">
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        <script src="script.js"></script>
                    </canvas>

                </div>


                <div class="tenant-table">

                    <table>
                        <thead>
                            <tr>
                                <th>Tenant Name</th>
                                <th>Room</th>
                                <th>Payment Status</th>
                                <th>Date of Payment</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {

                                $sql = "SELECT b.first_name, b.last_name, c.room_number, e.paid_on, e.due_date, f.status
                                FROM contract a
                                JOIN personal_info b ON b.user_id = a.user_id
                                JOIN user d ON d.user_id = a.user_id
                                JOIN room c ON d.room_id = c.room_id
                                JOIN payment e ON e.contract_id = a.contract_id
                                JOIN payment_status f ON e.payment_id = f.payment_id
                                ORDER BY b.first_name";



                                $result_query = mysqli_query($db_connection, $sql);
                                while ($result = mysqli_fetch_array($result_query)) {

                                    echo '<tr>
                            <td><input type="checkbox" /></td>
                            <td>' . $result['first_name'] . ' ' . $result['last_name'] . '</td>
                    <td>' . $result['room_number'] . '</td>
                    <td>' . $result['status'] . '</td>
                    <td>' . $result['paid_on'] . '</td>
                    <td>' . $result['status'] . '</td>
                    <td> 
                            <button class="btn btn-sm btn-outline-primary me-1"><a href="contract_CRUD/edit_contract.php?id=' . $result["contract_id"] . ' "><i class="fas fa-edit"></i></a></button>
                            <button class="btn btn-sm btn-outline-danger">  <a onclick=" confirmDelete(' . $result["contract_id"] . ')"><i class="fas fa-trash-alt"></i></a></button>         
            </td>
            </tr>';
                                }
                            } catch (Exception $e) {
                                echo "Error: " . $e->getMessage();
                            }


                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="report-buttons">
                <button class="btn-primary">Generate Report</button>
                <button class="btn-primary">Export (PDF/Excel)</button>
            </div>
        </div>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="script.js"></script>
</body>

</html>