#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}   LARAVEL 13 FEATURE GENERATOR V5      ${NC}"
echo -e "${BLUE}========================================${NC}"

read -p "Nama Folder Fitur (ex: Auth): " FEATURE_NAME
read -p "Nama Komponen/File (ex: User): " COMPONENT_NAME

# Format penamaan
TABLE_NAME=$(echo "$COMPONENT_NAME" | sed 's/\([a-z0-9]\)\([A-Z]\)/\1_\2/g' | tr '[:upper:]' '[:lower:]')
CONTROLLER_NAME="${COMPONENT_NAME}Controller"
SERVICE_NAME="${COMPONENT_NAME}Service"
REPOSITORY_NAME="${COMPONENT_NAME}Repository"
CREATE_REQUEST_NAME="Create${COMPONENT_NAME}Request"
UPDATE_REQUEST_NAME="Update${COMPONENT_NAME}Request"
SEEDER_NAME="${COMPONENT_NAME}Seeder"
FEATURE_PATH="app/Features/$FEATURE_NAME"

# Buat Semua Folder
mkdir -p "$FEATURE_PATH/Controllers"
mkdir -p "$FEATURE_PATH/Models"
mkdir -p "$FEATURE_PATH/Migrations"
mkdir -p "$FEATURE_PATH/Services"
mkdir -p "$FEATURE_PATH/Repositories"
mkdir -p "$FEATURE_PATH/Requests"
mkdir -p "$FEATURE_PATH/Seeders"

echo -e "\nPilih komponen yang ingin dibuat:"
echo "1) Full Set (Model, Controller, Migr, Service, Repos, Request, Seeder, Route)"
echo "2) Model, Migration & Seeder"
echo "3) Controller, Service, Repos, Request"
echo "4) Migration Saja"
read -p "Pilihan [1-4]: " CHOICE

# --- PERMISSION HANDLING ---
if [[ "$CHOICE" == "1" || "$CHOICE" == "3" ]]; then
    echo -e "\n${YELLOW}Konfigurasi Access Control (ACL):${NC}"
    echo "Pilih Group Permission:"
    
    # Ambil list group yang sudah ada dari AclService secara dinamis
    EXISTING_GROUPS=$(php artisan tinker --execute="print_r(array_keys((new App\Features\Acl\Services\AclService)->getDefinitions()))" | grep '\[.*\]' | sed 's/.*\[//;s/\].*//')
    
    i=1
    declare -a groups_array
    for g in $EXISTING_GROUPS; do
        echo "$i) $g"
        groups_array[$i]=$g
        ((i++))
    done
    echo "$i) [ Buat Group Baru ]"
    
    read -p "Pilihan Group [1-$i]: " PERM_CHOICE
    
    if [ "$PERM_CHOICE" -eq "$i" ]; then
        read -p "ID Group Baru (snake_case, ex: quality_control): " PERM_ID
        read -p "Label Group (ex: Quality Control): " PERM_LABEL
        read -p "Label untuk 'Read' (ex: View Quality Logs): " LABEL_READ
        read -p "Label untuk 'Create' (ex: Input New Check): " LABEL_CREATE
        
        # Inject ke AclService.php menggunakan PHP
        php -r "
        \$path = 'app/Features/Acl/Services/AclService.php';
        \$content = file_get_contents(\$path);
        \$newEntry = \"            '$PERM_ID' => [\\n\" .
                     \"                '$PERM_ID.read' => '$LABEL_READ',\\n\" .
                     \"                '$PERM_ID.create' => '$LABEL_CREATE',\\n\" .
                     \"                '$PERM_ID.update' => 'Modify $PERM_LABEL Entries',\\n\" .
                     \"                '$PERM_ID.delete' => 'Remove $PERM_LABEL Records',\\n\" .
                     \"            ],\\n\";
        \$updated = str_replace('// [AUTO_GEN_MARKER]', \$newEntry . '            // [AUTO_GEN_MARKER]', \$content);
        file_put_contents(\$path, \$updated);
        "
        echo -e "${GREEN}✅ Group permission '$PERM_LABEL' didaftarkan ke AclService.${NC}"
    else
        SELECTED_GROUP=${groups_array[$PERM_CHOICE]}
        # Kita perlu mencari ID teknis (key) dari group tersebut
        PERM_ID=$(php artisan tinker --execute="\$defs = (new App\Features\Acl\Services\AclService)->getDefinitions(); foreach(\$defs as \$k => \$v) { if(\$v['label'] == '$SELECTED_GROUP') { echo \$k; break; } }" | tail -n 1 | tr -d '"')
        
        # Fallback jika tidak ketemu lewat label, gunakan bash string manipulation
        if [ -z "$PERM_ID" ]; then
            PERM_ID=$(echo "$SELECTED_GROUP" | tr '[:upper:]' '[:lower:]' | tr ' ' '_')
        fi
        echo -e "${GREEN}✅ Menggunakan Group permission existing: $PERM_ID${NC}"
    fi
fi

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
    echo -e "${GREEN}✅ Model dibuat.${NC}"
}

gen_repository() {
    cat <<EOF > "$FEATURE_PATH/Repositories/$REPOSITORY_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Repositories;

use App\Features\\$FEATURE_NAME\Models\\$COMPONENT_NAME;
use Illuminate\Support\Collection;

class $REPOSITORY_NAME
{
    public function getAll(): Collection
    {
        return $COMPONENT_NAME::all();
    }

    public function findById(int \$id): ?$COMPONENT_NAME
    {
        return $COMPONENT_NAME::find(\$id);
    }

    public function create(array \$data): $COMPONENT_NAME
    {
        return $COMPONENT_NAME::create(\$data);
    }

    public function update(int \$id, array \$data): bool
    {
        \$record = $COMPONENT_NAME::findOrFail(\$id);
        return \$record->update(\$data);
    }

    public function delete(int \$id): bool
    {
        \$record = $COMPONENT_NAME::findOrFail(\$id);
        return \$record->delete();
    }
}
EOF
    echo -e "${GREEN}✅ Repository Layer dibuat.${NC}"
}

gen_service() {
    cat <<EOF > "$FEATURE_PATH/Services/$SERVICE_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Services;

use App\Features\\$FEATURE_NAME\Repositories\\$REPOSITORY_NAME;
use Illuminate\Support\Facades\Auth;

class $SERVICE_NAME
{
    protected \$repository;

    public function __construct($REPOSITORY_NAME \$repository)
    {
        \$this->repository = \$repository;
    }

    public function getAll()
    {
        return \$this->repository->getAll();
    }

    public function findById(int \$id)
    {
        return \$this->repository->findById(\$id);
    }

    public function create(array \$data)
    {
        return \$this->repository->create(\$data);
    }

    public function update(int \$id, array \$data)
    {
        return \$this->repository->update(\$id, \$data);
    }

    public function delete(int \$id)
    {
        return \$this->repository->delete(\$id);
    }
}
EOF
    echo -e "${GREEN}✅ Service Layer (with Repository) dibuat.${NC}"
}

gen_request() {
    # Create Request
    cat <<EOF > "$FEATURE_PATH/Requests/$CREATE_REQUEST_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Requests;

use Illuminate\Foundation\Http\FormRequest;

class $CREATE_REQUEST_NAME extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
EOF
    # Update Request
    cat <<EOF > "$FEATURE_PATH/Requests/$UPDATE_REQUEST_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Requests;

use Illuminate\Foundation\Http\FormRequest;

class $UPDATE_REQUEST_NAME extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
EOF
    echo -e "${GREEN}✅ Form Requests (Create & Update) dibuat.${NC}"
}

gen_controller() {
    cat <<EOF > "$FEATURE_PATH/Controllers/$CONTROLLER_NAME.php"
<?php

namespace App\Features\\$FEATURE_NAME\Controllers;

use App\Http\Controllers\Controller;
use App\Features\\$FEATURE_NAME\Services\\$SERVICE_NAME;
use App\Features\\$FEATURE_NAME\Requests\\$CREATE_REQUEST_NAME;
use App\Features\\$FEATURE_NAME\Requests\\$UPDATE_REQUEST_NAME;
use Illuminate\Http\JsonResponse;

class $CONTROLLER_NAME extends Controller
{
    protected \$service;

    public function __construct($SERVICE_NAME \$service)
    {
        \$this->service = \$service;
    }

    public function index(): JsonResponse
    {
        \$data = \$this->service->getAll();
        return response()->json(['message' => 'Success', 'data' => \$data]);
    }

    public function store($CREATE_REQUEST_NAME \$request): JsonResponse
    {
        \$data = \$this->service->create(\$request->validated());
        return response()->json(['message' => 'Created', 'data' => \$data], 201);
    }

    public function show(\$id): JsonResponse
    {
        \$data = \$this->service->findById(\$id);
        if (!\$data) {
            return response()->json(['message' => 'Not Found'], 404);
        }
        return response()->json(['message' => 'Success', 'data' => \$data]);
    }

    public function update($UPDATE_REQUEST_NAME \$request, \$id): JsonResponse
    {
        \$updated = \$this->service->update(\$id, \$request->validated());
        if (!\$updated) {
            return response()->json(['message' => 'Failed to update'], 400);
        }
        \$data = \$this->service->findById(\$id);
        return response()->json(['message' => 'Updated', 'data' => \$data]);
    }

    public function destroy(\$id): JsonResponse
    {
        \$deleted = \$this->service->delete(\$id);
        if (!\$deleted) {
            return response()->json(['message' => 'Failed to delete'], 400);
        }
        return response()->json(['message' => 'Deleted']);
    }
}
EOF
    echo -e "${GREEN}✅ Controller (Full CRUD with Service & Request) dibuat.${NC}"
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
        gen_model; gen_repository; gen_request; gen_service; gen_controller; gen_seeder
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
    2)
        gen_model; gen_seeder
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
    3)
        gen_repository; gen_request; gen_service; gen_controller
        ;;
    4)
        php artisan make:migration "create_${TABLE_NAME}_table" --path="$FEATURE_PATH/Migrations"
        ;;
esac

# Route handling (Option 1 & 3)
if [[ "$CHOICE" == "1" || "$CHOICE" == "3" ]]; then
    if [ ! -f "$FEATURE_PATH/routes.php" ]; then
        PID=$PERM_ID
        cat <<EOF > "$FEATURE_PATH/routes.php"
<?php

use Illuminate\Support\Facades\Route;
use App\Features\\$FEATURE_NAME\Controllers\\$CONTROLLER_NAME;

Route::middleware('permission:$PID.read')->group(function() {
    Route::get('/', [$CONTROLLER_NAME::class, 'index']);
    Route::get('/{id}', [$CONTROLLER_NAME::class, 'show']);
});

Route::middleware('permission:$PID.create')->group(function() {
    Route::post('/', [$CONTROLLER_NAME::class, 'store']);
});

Route::middleware('permission:$PID.update')->group(function() {
    Route::put('/{id}', [$CONTROLLER_NAME::class, 'update']);
});

Route::middleware('permission:$PID.delete')->group(function() {
    Route::delete('/{id}', [$CONTROLLER_NAME::class, 'destroy']);
});
EOF
        echo -e "${GREEN}✅ routes.php dibuat dan diamankan dengan permission '$PID'.${NC}"
    fi
fi

echo -e "\n${BLUE}Selesai! Struktur $FEATURE_NAME sudah lengkap.${NC}"
echo -e "${YELLOW}Jangan lupa jalankan 'php artisan migrate' dan klik 'Sync' di Access Control UI.${NC}"