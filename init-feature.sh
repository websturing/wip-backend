#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}=======================================${NC}"
echo -e "${BLUE}   LARAVEL 13 FEATURE GENERATOR (FIX) ${NC}"
echo -e "${BLUE}=======================================${NC}"

read -p "Nama Folder Fitur (ex: Auth): " FEATURE_NAME
read -p "Nama Komponen/File (ex: User): " COMPONENT_NAME

# Format penamaan
TABLE_NAME=$(echo "$COMPONENT_NAME" | sed 's/\([a-z0-9]\)\([A-Z]\)/\1_\2/g' | tr '[:upper:]' '[:lower:]')
CONTROLLER_NAME="${COMPONENT_NAME}Controller"
FEATURE_PATH="app/Features/$FEATURE_NAME"

# Buat Folder
mkdir -p "$FEATURE_PATH/Controllers"
mkdir -p "$FEATURE_PATH/Models"
mkdir -p "$FEATURE_PATH/Migrations"

echo -e "\nPilih komponen [1-4]: "
echo "1) Semuanya  2) Model & Migr  3) Controller  4) Migr Saja"
read -p "Pilihan: " CHOICE

# --- GENERATOR LOGIC ---

# FUNCTION: Generate Model
gen_model() {
    cat <<EOF > "$FEATURE_PATH/Models/$COMPONENT_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Models;

use Illuminate\Database\Eloquent\Model;

class $COMPONENT_NAME extends Model
{
    protected \$guarded = [];
}
EOF
    echo -e "${GREEN}✅ Model dibuat di $FEATURE_PATH/Models/${NC}"
}

# FUNCTION: Generate Controller
gen_controller() {
    cat <<EOF > "$FEATURE_PATH/Controllers/$CONTROLLER_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class $CONTROLLER_NAME extends Controller
{
    public function index()
    {
        return response()->json(['message' => 'Hello from $CONTROLLER_NAME']);
    }
}
EOF
    echo -e "${GREEN}✅ Controller dibuat di $FEATURE_PATH/Controllers/${NC}"
}

# EXECUTION
case $CHOICE in
    1)
        gen_model
        gen_controller
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
    2)
        gen_model
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
    3)
        gen_controller
        ;;
    4)
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
esac

# Create routes.php if option 1 or 3
if [[ "$CHOICE" == "1" || "$CHOICE" == "3" ]]; then
    if [ ! -f "$FEATURE_PATH/routes.php" ]; then
        cat <<EOF > "$FEATURE_PATH/routes.php"
<?php

use Illuminate\Support\Facades\Route;
use App\Features\\$FEATURE_NAME\Controllers\\$CONTROLLER_NAME;

Route::get('/', [$CONTROLLER_NAME::class, 'index']);
EOF
        echo -e "${GREEN}✅ routes.php dibuat.${NC}"
    fi
fi

echo -e "\n${BLUE}Selesai! File sekarang berada di folder yang benar.${NC}"