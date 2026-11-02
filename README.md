BÀI TẬP OOP: MINI CLINIC - ĐẶT LỊCH KHÁM

Thời gian: 2-3 ngày
Công nghệ: PHP 8 trở lên, chạy trên dòng lệnh
Không dùng: framework, Composer, database (dữ liệu lưu trong mảng)


1. MÔ TẢ

Viết chương trình quản lý lịch khám của một phòng khám nhỏ: đặt lịch, xác nhận,
hoàn thành, huỷ lịch và xem danh sách lịch của bác sĩ.


2. THÔNG TIN CẦN QUẢN LÝ

- Bác sĩ: họ tên, số điện thoại, chuyên khoa.
- Bệnh nhân: họ tên, số điện thoại.
- Loại khám:
    Khám thường        200.000 đồng
    Khám chuyên khoa   500.000 đồng
- Lịch khám: mã lịch, bác sĩ, bệnh nhân, loại khám, giờ khám (ví dụ "09:00"),
  trạng thái.
- Thông báo cho bệnh nhân khi đặt lịch và khi huỷ lịch, qua 2 kênh Email và SMS.
  Chỉ cần in nội dung ra màn hình.


3. QUY TẮC

- Một bác sĩ không được có 2 lịch cùng giờ. Lịch đã huỷ không tính.
- Lịch mới có trạng thái Chờ xác nhận.
- Trạng thái chỉ được đổi theo các hướng sau, trường hợp khác phải báo lỗi:
    Chờ xác nhận -> Đã xác nhận -> Hoàn thành
    Chờ xác nhận -> Đã huỷ
    Đã xác nhận  -> Đã huỷ


4. YÊU CẦU VỀ OOP

- Đóng gói: thuộc tính để private. Trạng thái lịch khám chỉ đổi được qua các
  phương thức confirm(), complete(), cancel() của lịch khám.
- Kế thừa: bác sĩ và bệnh nhân kế thừa từ lớp trừu tượng Person. Các loại khám
  kế thừa từ lớp trừu tượng Consultation.
- Đa hình: mỗi loại khám tự trả về phí của mình. Không dùng if/switch theo loại
  khám để tính phí.
- Trừu tượng: dùng interface Notifier cho các kênh thông báo. Lớp Clinic chỉ biết
  đến Notifier, không biết đến Email hay SMS.


5. KỊCH BẢN DEMO

Chạy lệnh "php run.php" phải cho đúng kết quả dưới đây.
Dữ liệu: bác sĩ An, bệnh nhân Bình, bệnh nhân Chi.

    Bước  Thao tác                           Kết quả
    1     Bình đặt khám thường lúc 09:00     Thành công, phí 200.000
    2     Chi đặt khám chuyên khoa lúc 09:00 Lỗi: trùng giờ
    3     Chi đặt khám chuyên khoa lúc 10:00 Thành công, phí 500.000
    4     Xác nhận lịch của Bình             Đã xác nhận
    5     Hoàn thành lịch của Bình           Hoàn thành
    6     Huỷ lịch của Bình                  Lỗi: lịch đã hoàn thành
    7     Huỷ lịch 10:00 của Chi             Đã huỷ
    8     Chi đặt khám thường lúc 10:00      Thành công, phí 200.000

Các lỗi phải được bắt và in ra, chương trình không được dừng giữa chừng.
Cuối cùng in danh sách lịch của bác sĩ An.

