#!/bin/bash

# Tanya nama fitur
read -p "Masukkan Nama Fitur (contoh: UserManagement): " FEATURE_NAME

# Konversi nama ke lowercase untuk folder & snake_case untuk tabel
LOWER_FEATURE=$(echo "$FEATURE_NAME" | tr '[:upper:]' '[:lower:]')
TABLE_NAME=$(echo "$FEATURE_NAME" | sed 's/\([a-z0-9]\)\([A-Z]\)/\1_\2/g' | tr '[:upper:]' '[:lower:]')

# 1. Buat struktur folder di app/Features
FEATURE_PATH="app/Features/$FEATURE_NAME"
mkdir -p "$FEATURE_PATH/Controllers"
mkdir -p "$FEATURE_PATH/Models"
mkdir -p "$FEATURE_PATH/Migrations"

echo "✅ Folder $FEATURE_PATH telah dibuat."

# 2. Buat Model di dalam folder fitur
php artisan make:model "Features/$FEATURE_NAME/Models/$FEATURE_NAME"

# 3. Buat Controller di dalam folder fitur
php artisan make:controller "Features/$FEATURE_NAME/Controllers/${FEATURE_NAME}Controller" --api

# 4. Buat Migration khusus di dalam folder fitur
# Kita gunakan flag --path agar file migration masuk ke folder fitur, bukan database/migrations
php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"

echo "----------------------------------------------------"
echo "🚀 Fitur [$FEATURE_NAME] siap dikerjakan!"
echo "📍 Model: $FEATURE_PATH/Models/$FEATURE_NAME.php"
echo "📍 Controller: $FEATURE_PATH/Controllers/${FEATURE_NAME}Controller.php"
echo "📍 Migration: $FEATURE_PATH/Migrations/ (Gunakan php artisan migrate --path=$FEATURE_PATH/Migrations)"