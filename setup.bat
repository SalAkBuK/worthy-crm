@echo off
setlocal enabledelayedexpansion

set "ROOT=%~dp0"
set "MYSQL_EXE=C:\xampp\mysql\bin\mysql.exe"

if not exist "%MYSQL_EXE%" (
  echo MySQL not found at "%MYSQL_EXE%".
  set /p "MYSQL_EXE=Enter full path to mysql.exe: "
)
if not exist "%MYSQL_EXE%" (
  echo ERROR: mysql.exe not found.
  exit /b 1
)

set "DB_NAME=CRM"
set /p "DB_NAME=Enter DB name [CRM]: "
if "%DB_NAME%"=="" set "DB_NAME=CRM"

set "DB_USER=root"
set /p "DB_USER=Enter DB user [root]: "
if "%DB_USER%"=="" set "DB_USER=root"

set /p "DB_PASS=Enter DB password (leave blank for none): "

if not exist "%ROOT%.env" (
  copy "%ROOT%.env.example" "%ROOT%.env" >nul
)

for %%G in ("%ROOT%.env") do set "ENV_PATH=%%~fG"

powershell -NoProfile -Command ^
  "$path='%ENV_PATH%';" ^
  "$text=Get-Content -Raw -Path $path;" ^
  "$text=$text -replace '^DB_NAME=.*','DB_NAME=%DB_NAME%';" ^
  "$text=$text -replace '^DB_USER=.*','DB_USER=%DB_USER%';" ^
  "$text=$text -replace '^DB_PASS=.*','DB_PASS=%DB_PASS%';" ^
  "[IO.File]::WriteAllText($path,$text,[Text.ASCIIEncoding]::new())"

set "MYSQL_ARGS=-u%DB_USER%"
if not "%DB_PASS%"=="" set "MYSQL_ARGS=%MYSQL_ARGS% -p%DB_PASS%"

echo Creating database "%DB_NAME%"...
"%MYSQL_EXE%" %MYSQL_ARGS% -e "CREATE DATABASE IF NOT EXISTS \`%DB_NAME%\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
if errorlevel 1 (
  echo ERROR: Failed to create database.
  exit /b 1
)

if not exist "%ROOT%database\\schema.sql" (
  echo ERROR: database\\schema.sql not found.
  exit /b 1
)

echo Importing schema...
"%MYSQL_EXE%" %MYSQL_ARGS% "%DB_NAME%" < "%ROOT%database\\schema.sql"
if errorlevel 1 (
  echo ERROR: Failed to import schema.
  exit /b 1
)

echo Applying migrations...
set "MIG_SQL=SET @col := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='leads' AND COLUMN_NAME='contact_phone'); SET @sql := IF(@col=0, 'ALTER TABLE leads ADD COLUMN contact_phone VARCHAR(40) NOT NULL DEFAULT '''';', 'SELECT 1;'); PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt; SET @col2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='users' AND COLUMN_NAME='email'); SET @sql2 := IF(@col2=0, 'ALTER TABLE users ADD COLUMN email VARCHAR(190) NULL;', 'SELECT 1;'); PREPARE stmt2 FROM @sql2; EXECUTE stmt2; DEALLOCATE PREPARE stmt2; SET @col3 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='users' AND COLUMN_NAME='contact_phone'); SET @sql3 := IF(@col3=0, 'ALTER TABLE users ADD COLUMN contact_phone VARCHAR(40) NULL;', 'SELECT 1;'); PREPARE stmt3 FROM @sql3; EXECUTE stmt3; DEALLOCATE PREPARE stmt3; SET @col4 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='users' AND COLUMN_NAME='agent_name'); SET @sql4 := IF(@col4=0, 'ALTER TABLE users ADD COLUMN agent_name VARCHAR(120) NULL;', 'SELECT 1;'); PREPARE stmt4 FROM @sql4; EXECUTE stmt4; DEALLOCATE PREPARE stmt4; SET @col5 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='users' AND COLUMN_NAME='rera_number'); SET @sql5 := IF(@col5=0, 'ALTER TABLE users ADD COLUMN rera_number VARCHAR(50) NULL;', 'SELECT 1;'); PREPARE stmt5 FROM @sql5; EXECUTE stmt5; DEALLOCATE PREPARE stmt5; SET @col6 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='users' AND COLUMN_NAME='properties_scope'); SET @sql6 := IF(@col6=0, 'ALTER TABLE users ADD COLUMN properties_scope ENUM(''OFF_PLAN'',''SECONDARY'',''BOTH'') NULL;', 'SELECT 1;'); PREPARE stmt6 FROM @sql6; EXECUTE stmt6; DEALLOCATE PREPARE stmt6; SET @col7 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='users' AND COLUMN_NAME='photo_path'); SET @sql7 := IF(@col7=0, 'ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) NULL;', 'SELECT 1;'); PREPARE stmt7 FROM @sql7; EXECUTE stmt7; DEALLOCATE PREPARE stmt7; SET @col8 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='users' AND COLUMN_NAME='is_active'); SET @sql8 := IF(@col8=0, 'ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;', 'SELECT 1;'); PREPARE stmt8 FROM @sql8; EXECUTE stmt8; DEALLOCATE PREPARE stmt8; SET @f1 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='next_followup_at'); SET @sf1 := IF(@f1=0, 'ALTER TABLE lead_followups ADD COLUMN next_followup_at DATETIME NULL;', 'SELECT 1;'); PREPARE stmt9 FROM @sf1; EXECUTE stmt9; DEALLOCATE PREPARE stmt9; SET @f2 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='buy_property_type'); SET @sf2 := IF(@f2=0, 'ALTER TABLE lead_followups ADD COLUMN buy_property_type ENUM(''READY_TO_MOVE'',''OFF_PLAN'') NULL;', 'SELECT 1;'); PREPARE stmt10 FROM @sf2; EXECUTE stmt10; DEALLOCATE PREPARE stmt10; SET @f3 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='size_sqft'); SET @sf3 := IF(@f3=0, 'ALTER TABLE lead_followups ADD COLUMN size_sqft INT UNSIGNED NULL;', 'SELECT 1;'); PREPARE stmt11 FROM @sf3; EXECUTE stmt11; DEALLOCATE PREPARE stmt11; SET @f4 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='location'); SET @sf4 := IF(@f4=0, 'ALTER TABLE lead_followups ADD COLUMN location VARCHAR(190) NULL;', 'SELECT 1;'); PREPARE stmt12 FROM @sf4; EXECUTE stmt12; DEALLOCATE PREPARE stmt12; SET @f5 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='building'); SET @sf5 := IF(@f5=0, 'ALTER TABLE lead_followups ADD COLUMN building VARCHAR(190) NULL;', 'SELECT 1;'); PREPARE stmt13 FROM @sf5; EXECUTE stmt13; DEALLOCATE PREPARE stmt13; SET @f6 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='beds'); SET @sf6 := IF(@f6=0, 'ALTER TABLE lead_followups ADD COLUMN beds TINYINT UNSIGNED NULL;', 'SELECT 1;'); PREPARE stmt14 FROM @sf6; EXECUTE stmt14; DEALLOCATE PREPARE stmt14; SET @f7 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='budget'); SET @sf7 := IF(@f7=0, 'ALTER TABLE lead_followups ADD COLUMN budget DECIMAL(12,2) NULL;', 'SELECT 1;'); PREPARE stmt15 FROM @sf7; EXECUTE stmt15; DEALLOCATE PREPARE stmt15; SET @f8 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='downpayment'); SET @sf8 := IF(@f8=0, 'ALTER TABLE lead_followups ADD COLUMN downpayment DECIMAL(12,2) NULL;', 'SELECT 1;'); PREPARE stmt16 FROM @sf8; EXECUTE stmt16; DEALLOCATE PREPARE stmt16; SET @f9 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='cheques'); SET @sf9 := IF(@f9=0, 'ALTER TABLE lead_followups ADD COLUMN cheques TINYINT UNSIGNED NULL;', 'SELECT 1;'); PREPARE stmt17 FROM @sf9; EXECUTE stmt17; DEALLOCATE PREPARE stmt17; SET @f10 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='rent_per_month'); SET @sf10 := IF(@f10=0, 'ALTER TABLE lead_followups ADD COLUMN rent_per_month DECIMAL(12,2) NULL;', 'SELECT 1;'); PREPARE stmt18 FROM @sf10; EXECUTE stmt18; DEALLOCATE PREPARE stmt18; SET @f11 := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='%DB_NAME%' AND TABLE_NAME='lead_followups' AND COLUMN_NAME='rent_per_year_budget'); SET @sf11 := IF(@f11=0, 'ALTER TABLE lead_followups ADD COLUMN rent_per_year_budget DECIMAL(12,2) NULL;', 'SELECT 1;'); PREPARE stmt19 FROM @sf11; EXECUTE stmt19; DEALLOCATE PREPARE stmt19;"
"%MYSQL_EXE%" %MYSQL_ARGS% "%DB_NAME%" -e "%MIG_SQL%"

echo Setup complete.
exit /b 0
