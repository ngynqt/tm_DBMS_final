# MySQL Docker Replication - Complete Setup Script
# Run this in PowerShell

Write-Host "=== MySQL Docker Replication Setup ===" -ForegroundColor Green
Write-Host ""

# Step 1: Stop and remove existing containers
Write-Host "Step 1: Cleaning up existing containers..." -ForegroundColor Yellow
docker-compose down -v 2>$null

# Step 2: Start fresh containers
Write-Host "Step 2: Starting fresh containers..." -ForegroundColor Yellow
docker-compose up -d

# Step 3: Wait for MySQL to be ready
Write-Host "Step 3: Waiting for MySQL to initialize (30 seconds)..." -ForegroundColor Yellow
Start-Sleep -Seconds 30

# Step 4: Import schema to Master ONLY
Write-Host "Step 4: Creating database schema on Master..." -ForegroundColor Yellow
Get-Content docker\init_replication.sql | docker exec -i mysql-master mysql -uroot -prootpassword

# Step 5: Get Master status
Write-Host "Step 5: Getting Master status..." -ForegroundColor Yellow
$masterStatus = docker exec -i mysql-master mysql -uroot -prootpassword -e "SHOW MASTER STATUS\G" 2>$null
$logFile = ($masterStatus | Select-String "File:").ToString().Split(':')[1].Trim()
$logPos = ($masterStatus | Select-String "Position:").ToString().Split(':')[1].Trim()

Write-Host "  Master Log File: $logFile" -ForegroundColor Cyan
Write-Host "  Master Log Position: $logPos" -ForegroundColor Cyan

# Step 6: Set Slave server-id
Write-Host "Step 6: Configuring Slave server-id..." -ForegroundColor Yellow
docker exec -i mysql-slave mysql -uroot -prootpassword -e "SET GLOBAL server_id = 2" 2>$null

# Step 7: Setup replication on Slave
Write-Host "Step 7: Setting up replication on Slave..." -ForegroundColor Yellow
$replicationCmd = @"
STOP SLAVE;
RESET SLAVE;
CHANGE MASTER TO
  MASTER_HOST='mysql-master',
  MASTER_USER='repl_user',
  MASTER_PASSWORD='repl_password',
  MASTER_LOG_FILE='$logFile',
  MASTER_LOG_POS=$logPos;
START SLAVE;
"@

$replicationCmd | docker exec -i mysql-slave mysql -uroot -prootpassword 2>$null

# Step 8: Verify replication
Write-Host "Step 8: Verifying replication status..." -ForegroundColor Yellow
Start-Sleep -Seconds 3

$slaveStatus = docker exec -i mysql-slave mysql -uroot -prootpassword -e "SHOW SLAVE STATUS\G" 2>$null
$ioRunning = ($slaveStatus | Select-String "Slave_IO_Running:").ToString().Split(':')[1].Trim()
$sqlRunning = ($slaveStatus | Select-String "Slave_SQL_Running:").ToString().Split(':')[1].Trim()

Write-Host ""
Write-Host "=== Replication Status ===" -ForegroundColor Green
Write-Host "Slave_IO_Running: $ioRunning" -ForegroundColor $(if($ioRunning -eq "Yes"){"Green"}else{"Red"})
Write-Host "Slave_SQL_Running: $sqlRunning" -ForegroundColor $(if($sqlRunning -eq "Yes"){"Green"}else{"Red"})

# Step 9: Test replication
if($ioRunning -eq "Yes" -and $sqlRunning -eq "Yes") {
    Write-Host ""
    Write-Host "Step 9: Testing replication..." -ForegroundColor Yellow
    
    # Insert on Master
    docker exec -i mysql-master mysql -uroot -prootpassword my_store -e "INSERT INTO products (name, price, brand, rating_average, review_count) VALUES ('Replication Test', 999999, 'TestBrand', 5.0, 1000)" 2>$null
    
    Start-Sleep -Seconds 2
    
    # Query on Slave
    $slaveCount = docker exec -i mysql-slave mysql -uroot -prootpassword my_store -e "SELECT COUNT(*) as cnt FROM products" 2>$null
    $count = ($slaveCount | Select-String "\d+").Matches[0].Value
    
    Write-Host "  Products replicated to Slave: $count" -ForegroundColor Cyan
    
    if([int]$count -gt 0) {
        Write-Host ""
        Write-Host "✓ SUCCESS! Replication is working!" -ForegroundColor Green
    } else {
        Write-Host ""
        Write-Host "✗ WARNING: Data not replicated" -ForegroundColor Red
    }
} else {
    Write-Host ""
    Write-Host "✗ ERROR: Replication not running correctly" -ForegroundColor Red
    Write-Host "Check logs: docker logs mysql-slave" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== Setup Complete ===" -ForegroundColor Green
Write-Host "Master: localhost:3308" -ForegroundColor Cyan
Write-Host "Slave: localhost:3307" -ForegroundColor Cyan
