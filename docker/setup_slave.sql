-- Setup Slave Replication
-- Run this inside mysql-slave container

STOP SLAVE;

CHANGE MASTER TO
  MASTER_HOST='mysql-master',
  MASTER_USER='repl_user',
  MASTER_PASSWORD='repl_password',
  MASTER_LOG_FILE='binlog.000002',
  MASTER_LOG_POS=157;

START SLAVE;

SHOW SLAVE STATUS\G
