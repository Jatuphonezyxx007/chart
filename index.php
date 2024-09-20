<?php
// connectdb.php - เชื่อมต่อกับฐานข้อมูล
$servername = "localhost"; // แก้ไขตามที่อยู่เซิร์ฟเวอร์
$username = "root"; // แก้ไขตามชื่อผู้ใช้
$password = ""; // แก้ไขตามรหัสผ่าน
$dbname = "chart"; // แก้ไขตามชื่อฐานข้อมูล

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลยอดขายรวมตามวัน
$sql = "SELECT DATE(orders.date) AS sale_date, info.name, SUM(orders.total) AS total_sales 
        FROM orders 
        JOIN info ON orders.product = info.id 
        GROUP BY sale_date, info.name 
        ORDER BY sale_date";

$result = $conn->query($sql);

$dates = [];
$salesData = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $date = $row['sale_date'];
        $name = $row['name'];
        $total_sales = $row['total_sales'];

        if (!isset($dates[$date])) {
            $dates[$date] = [];
        }
        
        $dates[$date][$name] = $total_sales; // จัดเก็บยอดขายตามวันที่และชื่อสินค้า
    }
}

$conn->close();

// เตรียมข้อมูลสำหรับกราฟ
$labels = array_keys($dates);
$productNames = array_reduce($dates, function($carry, $item) {
    return array_unique(array_merge($carry, array_keys($item)));
}, []);

$dataSets = [];

foreach ($productNames as $productName) {
    $dataSets[$productName] = array_fill(0, count($labels), 0); // เตรียม array สำหรับแต่ละชื่อสินค้า

    foreach ($dates as $i => $sales) {
        if (isset($sales[$productName])) {
            $dataSets[$productName][array_search($i, $labels)] = $sales[$productName];
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        .center-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* ใช้ความสูง 100% ของ viewport */
        }
    </style>
</head>
<body>
    <div class="center-container">
        <div class="container">
            <div>
                <canvas id="myChart"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
      const ctx = document.getElementById('myChart').getContext('2d');
      const labels = <?php echo json_encode($labels); ?>; // ข้อมูลวัน
      const dataSets = <?php echo json_encode($dataSets); ?>; // ข้อมูลยอดขายตามสินค้า

      function getRandomColor() {
        const letters = '0123456789ABCDEF';
        let color = '#';
        for (let i = 0; i < 6; i++) {
          color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
      }

      const datasets = Object.keys(dataSets).map((productName) => {
        return {
          label: productName,
          data: dataSets[productName],
          backgroundColor: getRandomColor(),
          borderColor: getRandomColor(),
          borderWidth: 1
        };
      });

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: datasets
        },
        options: {
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    </script>
</body>
</html>
