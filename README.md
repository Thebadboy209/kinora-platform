# KINORA — Premium Video Platform (PHP + JSON)

Một nền tảng video responsive gồm thư viện khám phá, trình phát HTML5, hồ sơ creator, phần mô tả/bản quyền và trang quản trị. **Không cần MySQL, Node.js hay Composer.** Chỉ quản trị viên đăng video và tự quản lý dữ liệu.

## 1. Yêu cầu hosting

- PHP 8.2+; extension `fileinfo`, `session`, `json` và **cURL** để upload lên pone.rs.
- HTTPS cho website, đặc biệt cho trang `/admin/`.
- Hosting có quyền ghi vào `storage/` (đề xuất quyền thư mục 750, file 640; điều chỉnh theo user của web server).
- Apache hỗ trợ `.htaccess`, hoặc Nginx cấu hình chặn `storage/` (xem bên dưới).
- Video lớn: cần cấu hình `upload_max_filesize`, `post_max_size`, `max_input_time`, giới hạn request của web server/hosting. **Giới hạn pone.rs ghi trong API có thể cao hơn giới hạn hosting của bạn.**

## 2. Cài đặt nhanh

1. Giải nén ZIP và tải thư mục mã nguồn lên hosting (ví dụ `public_html/`).
2. Sao chép `config.example.php` thành `config.php` trên hosting và mở `config.php`, thay:

   ```php
   const INSTALL_SECRET = 'CHANGE_THIS_TO_A_LONG_PRIVATE_INSTALL_SECRET';
   ```

   bằng một chuỗi **riêng tư, ngẫu nhiên, tối thiểu 20 ký tự**, ví dụ dùng trình quản lý mật khẩu để sinh. Không chia sẻ chuỗi này hoặc đưa vào Git công khai.
3. Nếu bạn đặt source trong thư mục con `/kinora`, cập nhật `BASE_PATH = '/kinora'`. Khi đặt tại gốc domain, giữ `BASE_PATH = ''`.
4. Đảm bảo `storage/` có thể ghi và **không thể truy cập công khai**.
5. Truy cập `https://ten-mien-cua-ban/admin/`, nhập `INSTALL_SECRET` và mật khẩu quản trị (tối thiểu 12 ký tự).
6. Đăng nhập Studio → **Creators** (tạo chủ sở hữu) → **Upload Studio** (đăng video).
7. Trong **Settings**, đổi tên nền tảng, email liên hệ, màu chủ đạo và logo tùy chọn.

> Không có mật khẩu admin mặc định. `INSTALL_SECRET` chỉ dùng một lần để tạo tài khoản quản trị đầu tiên; khi admin đã tồn tại, màn hình thiết lập sẽ bị khóa.

## 3. Cách đăng video

Trong `Studio → Upload studio`:

- **Tải tệp**: chọn hoặc kéo thả MP4/WebM/MOV/M4V. PHP backend gửi `multipart/form-data` có `files[]` tới `https://pone.rs/upload` rồi trích URL từ JSON phản hồi.
- **Dán URL trực tiếp**: nếu có URL video sẵn, điền vào ô `Direct video URL`.
- Nhập tiêu đề, mô tả, ảnh thumbnail (URL), creator, nguồn gốc/bản quyền, ngày đăng hiển thị, view/like/dislike/comment/shares và trạng thái hiển thị.
- Chọn **Save draft** hoặc **Publish video**.

**Lưu ý:** pone.rs không được tích hợp như dịch vụ chuyển mã/streaming. URL trả về có thể là trang xem tệp hoặc không phát trực tiếp bằng HTML5. Phải **kiểm tra video phát được** trước khi xuất bản. Hosting của bạn phải bật extension `curl`. Nếu API trả JSON chưa được hỗ trợ, công cụ báo lỗi thay vì báo thành công sai; bạn vẫn có thể lấy liên kết phù hợp rồi dán thủ công.

Không có công cụ nào trong source xác minh bản quyền video tự động. `original_date`, owner, view, like, dislike do quản trị viên khai báo, không phải thông số đã được xác minh/đếm thực tế.

## 4. Bình luận mô phỏng

Trong `Studio → Comments`, chọn video → nhập số bình luận → Generate. Bình luận được lưu thật vào JSON và có nhãn **Mô phỏng** trên giao diện công khai. Mọi bình luận được tạo tại đây là dữ liệu minh họa, không phải lượt tương tác thật. Có thể thêm bình luận thủ công có nhãn **Admin nhập**, sửa, xóa và xóa toàn bộ bình luận demo của một video.

## 5. Sao lưu/khôi phục

Trong `Studio → Settings`:
- **Download JSON backup**: xuất `videos`, `creators`, `comments`, `settings` (không có mật khẩu admin).
- **Restore backup**: nhập đúng tệp JSON được xuất bởi phiên bản này; dữ liệu sẽ được kiểm tra cơ bản và thay thế.
- Mỗi lần ghi dữ liệu, ứng dụng tạo bản sao đầu tiên trong ngày cho từng tệp ở `storage/backups/`.

Nên sao lưu định kỳ và giữ bản gốc video riêng biệt. Việc xóa video khỏi website chỉ xóa metadata, **không xóa tệp đã upload khỏi pone.rs**.

## 6. Bảo mật quan trọng

Dữ liệu `storage/*.json` và `storage/backups/*` có thể chứa thông tin riêng tư và phải được chặn truy cập HTTP:

- **Apache**: file `storage/.htaccess` đã chứa `Require all denied`.
- **Nginx**: bắt buộc thêm quy tắc chặn truy cập `/storage/` nếu đặt trong web root:

  ```nginx
  location ^~ /storage/ { deny all; return 403; }
  location = /config.php { deny all; return 403; }
  ```

- **Khuyến nghị tốt nhất**: chuyển thư mục `storage` ra **ngoài** `public_html`, rồi sửa `STORAGE_PATH` trong `config.php` thành đường dẫn tuyệt đối đúng của hosting. Nếu PHP worker không thể ghi, điều chỉnh quyền file cho user chạy PHP, không dùng `777`.
- Nếu dùng Apache, `.htaccess` root chặn truy cập source `config.php`, `README.md`. Trên Nginx, cấu hình server phù hợp.
- Không đăng `config.php`, tệp JSON, file backup lên Git công khai. Repository có `.gitignore` để loại trừ các tệp này. Chỉ `config.example.php` được commit.
- Chỉ upload những video được phép sử dụng. Cần kiểm tra chính sách lưu trữ và quyền truy cập của dịch vụ bên thứ ba.
- Bật TLS/HTTPS để bảo vệ mật khẩu và session.

## 7. Cấu trúc mã nguồn

```text
kinora-platform/
├── index.php                # Home / featured / latest / popular
├── explore.php              # Search, sort, category
├── watch.php                # Player / attribution / comments
├── creators.php             # Creator directory
├── creator.php              # Creator profile
├── page.php                 # About, Contact, Copyright, Privacy, Terms
├── sitemap.php              # Public pages & videos
├── robots.txt
├── config.php               # Deployment settings + installation secret
├── includes/
│   ├── core.php             # JSON storage / sessions / access control
│   ├── icons.php            # Bundled SVG icons
│   └── ui.php               # Shared UI components
├── admin/
│   ├── index.php            # Admin dashboard & forms
│   ├── action.php           # Secure authenticated write operations
│   ├── upload.php           # Upload to pone.rs via cURL
│   └── data.php             # Export/import JSON backups
├── assets/
│   ├── css/style.css
│   ├── js/app.js
│   └── favicon.svg
└── storage/
    ├── .htaccess            # Must deny direct HTTP access
    ├── index.php
    └── backups/
```

Các file JSON được tạo tự động sau khi lưu; source mới hoàn toàn không chứa video hoặc số liệu minh họa giả.

## 8. Kiểm thử

Đã chạy `php -l` trên tất cả file PHP và kiểm thử trên PHP built-in server với các thao tác: tạo admin, tạo creator, publish video bằng URL, mở trang xem, tạo 5 bình luận demo, duplicate và delete. Chưa kiểm thử upload trực tiếp tới pone.rs trong môi trường thử nghiệm (máy thử không có extension PHP cURL và không có Internet trực tiếp). Trước khi đưa vào sử dụng, hãy kiểm tra endpoint upload trên hosting thực tế.

## 9. Giới hạn hiện tại

- Không có tài khoản công khai và hệ thống bình luận người dùng thật.
- Không có tính năng thống kê view/like/dislike tự động; tất cả là metadata admin cung cấp.
- Không mã hóa/chuyển mã video; trình duyệt phải hỗ trợ codec và URL nguồn phải cho phép tải/phát trực tiếp.
- Dịch vụ lưu trữ bên ngoài có thể thay đổi URL, xóa hoặc giới hạn tệp.
- JSON phù hợp nền tảng một quản trị viên hoặc thư viện nhỏ/vừa; lượng dữ liệu rất lớn hoặc nhiều người quản trị đồng thời nên cân nhắc database.
- Các trang Terms/Privacy/Copyright là mẫu nội dung cơ bản; cần cập nhật theo thực tế hoạt động trước khi công bố chính thức.

---

Tài liệu API tham khảo: https://pone.rs/api

## 10. Triển khai từ GitHub

1. Clone repository hoặc tải mã nguồn về hosting.
2. Sao chép `config.example.php` thành `config.php` (không đưa `config.php` lên Git).
3. Tạo `INSTALL_SECRET` duy nhất và dài từ 20 ký tự trở lên.
4. Đảm bảo thư mục `storage/` có quyền ghi và không thể truy cập công khai qua HTTP.
5. Mở `/admin/` để tạo tài khoản quản trị đầu tiên.

**Quan trọng:** GitHub lưu mã nguồn, không tự động chạy PHP hay host website. Cần PHP hosting riêng.