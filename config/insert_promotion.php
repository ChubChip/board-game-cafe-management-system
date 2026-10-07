<?php
session_start();
require_once 'connect.php'; // เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. รับค่าจากฟอร์ม
    $title = $_POST['title'];
    $description = $_POST['description'];
    $button_text = $_POST['button_text'] ?: 'ดูรายละเอียด';
    $action_link = $_POST['action_link'] ?: '#';
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    // 2. จัดการอัปโหลดรูปภาพ
    $image_url = ''; // เตรียมตัวแปรเก็บที่อยู่รูป
    if (isset($_FILES['promo_image']) && $_FILES['promo_image']['error'] == 0) {
        
        $target_dir = "../img/promotions/"; // โฟลเดอร์ที่เตรียมไว้
        
        // ดึงนามสกุลไฟล์ออกมา (เช่น .jpg, .png)
        $file_extension = pathinfo($_FILES["promo_image"]["name"], PATHINFO_EXTENSION);
        
        // ตั้งชื่อไฟล์ใหม่ด้วยการสุ่ม + เวลา เพื่อไม่ให้ชื่อซ้ำกัน
        $new_filename = "promo_" . time() . "_" . rand(1000,9999) . "." . $file_extension;
        $target_file = $target_dir . $new_filename;

        // ย้ายไฟล์จากเครื่องลูกค้าไปที่โฟลเดอร์โปรเจกต์
        if (move_uploaded_file($_FILES["promo_image"]["tmp_name"], $target_file)) {
            // สำเร็จ! บันทึก path รูปแบบนี้ลง Database
            $image_url = "../img/promotions/" . $new_filename; 
        } else {
            die("เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ");
        }
    } else {
        die("กรุณาอัปโหลดรูปภาพ");
    }

    // 3. บันทึกลง Database
    // ใช้ Prepared Statement เพื่อความปลอดภัย
    $sql = "INSERT INTO promotions (title, description, image_url, button_text, action_link, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // ผูกตัวแปร (s = string)
        $stmt->bind_param("ssssssss", $title, $description, $image_url, $button_text, $action_link, $start_date, $end_date, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "เพิ่มโปรโมชั่นเรียบร้อยแล้ว!";
            // เด้งกลับไปหน้าเพิ่มโปรโมชั่น หรือหน้าจัดการโปรโมชั่นที่คุณต้องการ
            echo "<script>alert('เพิ่มโปรโมชั่นสำเร็จ!'); window.location.href='../admin/add_promotion.php';</script>";
            exit();
        } else {
            echo "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "เกิดข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error;
    }
}
$conn->close();
?>