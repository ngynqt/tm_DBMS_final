# 🚀 QUICK START - Chạy ngay trong 5 phút

## Bước 1: Start Docker (1 phút)

```bash
cd d:\QuTriCSDL\tm_DBMS_final
docker-compose up -d
```

Đợi containers khởi động...

## Bước 2: Setup Replication (2 phút)

### 2.1. Lấy Master status

```bash
docker exec -it mysql-master mysql -uroot -prootpassword -e "SHOW MASTER STATUS\G"
```

Ghi lại: **File** và **Position**

### 2.2. Config Slave

```bash
docker exec -it mysql-slave mysql -uroot -prootpassword
```

Trong MySQL prompt:

```sql
CHANGE MASTER TO
  MASTER_HOST='mysql-master',
  MASTER_USER='repl_user',
  MASTER_PASSWORD='repl_password',
  MASTER_LOG_FILE='mysql-bin.000003',  -- ← GIÁ TRỊ TỪ BƯỚC 2.1
  MASTER_LOG_POS=157;                   -- ← GIÁ TRỊ TỪ BƯỚC 2.1

START SLAVE;
SHOW SLAVE STATUS\G
EXIT;
```

✅ Kiểm tra: `Slave_IO_Running: Yes` và `Slave_SQL_Running: Yes`

## Bước 3: Test (30 giây)

```bash
# Insert test data
docker exec -it mysql-master mysql -uroot -prootpassword my_store -e "INSERT INTO products (name, price, brand) VALUES ('Test', 100000, 'Nike')"

# Verify on Slave
docker exec -it mysql-slave mysql -uroot -prootpassword my_store -e "SELECT * FROM products WHERE name='Test'"
```

✅ Nếu thấy data → **Replication hoạt động!**

## Bước 4: Generate 1M rows (1-2 phút)

```bash
php scripts/generate_1m_products.php
```

## Bước 5: Benchmark

```bash
php scripts/benchmark.php
```

---

## ✅ DONE!

**Bạn vừa hoàn thành:**
- ✓ MySQL Docker containers (Master + Slave)
- ✓ Replication hoạt động
- ✓ Insert 1 million rows
- ✓ Performance test

**Xem chi tiết**: [DOCKER_GUIDE.md](DOCKER_GUIDE.md)
