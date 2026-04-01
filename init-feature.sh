#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   LARAVEL 13 FEATURE GENERATOR V3      ${NC}"
echo -e "${BLUE}========================================${NC}"

read -p "Nama Folder Fitur (ex: Auth): " FEATURE_NAME
read -p "Nama Komponen/File (ex: User): " COMPONENT_NAME

# Format penamaan
TABLE_NAME=$(echo "$COMPONENT_NAME" | sed 's/\([a-z0-9]\)\([A-Z]\)/\1_\2/g' | tr '[:upper:]' '[:lower:]')
CONTROLLER_NAME="${COMPONENT_NAME}Controller"
SERVICE_NAME="${COMPONENT_NAME}Service"
SEEDER_NAME="${COMPONENT_NAME}Seeder"
FEATURE_PATH="app/Features/$FEATURE_NAME"

# Buat Semua Folder
mkdir -p "$FEATURE_PATH/Controllers"
mkdir -p "$FEATURE_PATH/Models"
mkdir -p "$FEATURE_PATH/Migrations"
mkdir -p "$FEATURE_PATH/Services"
mkdir -p "$FEATURE_PATH/Seeders"

echo -e "\nPilih komponen yang ingin dibuat:"
echo "1) Full Set (Model, Controller, Migr, Service, Seeder, Route)"
echo "2) Model, Migration & Seeder"
echo "3) Controller & Service"
echo "4) Migration Saja"
read -p "Pilihan [1-4]: " CHOICE

# --- FUNCTIONS ---

gen_model() {
    cat <<EOF > "$FEATURE_PATH/Models/$COMPONENT_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class $COMPONENT_NAME extends Model
{
    use HasFactory;
    protected \$guarded = [];
}
EOF
    echo -e "${GREEN}✅ Model & Factory Support dibuat.${NC}"
}

gen_service() {
    cat <<EOF > "$FEATURE_PATH/Services/$SERVICE_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Services;

class $SERVICE_NAME
{
    public function getAll()
    {
        // Logika Bisnis Disini
        return [];
    }
}
EOF
    echo -e "${GREEN}✅ Service Layer dibuat.${NC}"
}

gen_controller() {
    cat <<EOF > "$FEATURE_PATH/Controllers/$CONTROLLER_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Controllers;

use App\Http\Controllers\Controller;
use App\Features\\$FEATURE_NAME\Services\\$SERVICE_NAME;
use Illuminate\Http\Request;

class $CONTROLLER_NAME extends Controller
{
    protected \$service;

    public function __construct($SERVICE_NAME \$service)
    {
        \$this->service = \$service;
    }

    public function index()
    {
        \$data = \$this->service->getAll();
        return response()->json(['message' => 'Success', 'data' => \$data]);
    }
}
EOF
    echo -e "${GREEN}✅ Controller (with Service Injection) dibuat.${NC}"
}

gen_seeder() {
    cat <<EOF > "$FEATURE_PATH/Seeders/$SEEDER_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Seeders;

use Illuminate\Database\Seeder;
use App\Features\\$FEATURE_NAME\Models\\$COMPONENT_NAME;

class $SEEDER_NAME extends Seeder
{
    public function run(): void
    {
        // \$COMPONENT_NAME::create(['name' => 'Sample Data']);
    }
}
EOF
    echo -e "${GREEN}✅ Seeder dibuat.${NC}"
}

# --- EXECUTION ---
case $CHOICE in
    1)
        gen_model; gen_service; gen_controller; gen_seeder
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
    2)
        gen_model; gen_seeder
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
    3)
        gen_service; gen_controller
        ;;
    4)
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
esac

# Route handling (Option 1 & 3)
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

echo -e "\n${BLUE}Selesai! Struktur $FEATURE_NAME sudah lengkap.${NC}"