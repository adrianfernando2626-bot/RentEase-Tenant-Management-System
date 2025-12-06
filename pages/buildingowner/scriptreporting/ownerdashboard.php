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
    <link rel="stylesheet" href="../../css/styles.css">
    <title>Document</title>
</head>

<body>

    <div class="users-container">
        <div class="users">
            <h4 class='nav_icon'>Total Tenants: <i class="bx bx-user"></i></h4>

            <p class="number-size"><strong><?php echo $rw1['total_tenant']; ?></strong></p>
        </div>

        <div class="users1">
            <h4 class='nav_icon'>Payments on Time: <i class="bx bx-sort"></i></h4>
            <?php
            $sql = 'SELECT paymentontime FROM payment_summary';
            $rs = mysqli_query($db_connection, $sql);
            $rw = mysqli_fetch_assoc($rs);
            ?>
            <p class="number-size"><strong><?php echo $rw['paymentontime']; ?></strong></p>
        </div>

        <div class="users">
            <h4 class='nav_icon'>Late Payment: <i class="bx bx-box"></i></h4>
            <?php
            $sql2 = 'SELECT paymentlatetime FROM payment_summary';
            $rs2 = mysqli_query($db_connection, $sql2);
            $rw2 = mysqli_fetch_assoc($rs2);
            ?>
            <p class="number-size"><strong><?php echo $rw2['paymentlatetime']; ?></strong></p>
        </div>
    </div>
    <div class="tablexchart">
        <div class="chart">
            <canvas id="myChart">
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script src="script.js"></script>
            </canvas>

        </div>
        <div class="table">
            <table>
                <tr>
                    <th>Tenant Name</th>
                    <th>Room Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php

                try {
                    $sql = "Select a.first_name, a.last_name, b.room_number, a.account_status 
                        from userall a
                        JOIN room b ON b.room_id = a.room_id";
                    $rs = mysqli_query($db_connection, $sql);
                    while ($rw = mysqli_fetch_array($rs)) {
                        echo '                
                        <tr>
                            <td>' . $rw['first_name'] . ' ' . $rw['last_name'] . '</td>
                            <td>' . $rw['room_number'] . '</td>
                            <td>' . $rw['account_status'] . '</td>
                            <td><button>edit</button> <button>delete</button></td>
                        </tr>
                        ';
                    }
                } catch (Exception $e) {
                    echo "Error: " . $e->getMessage();
                }
                ?>


            </table>

        </div>
    </div>




</body>

</html>