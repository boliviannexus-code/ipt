<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::register();
    }

    public function down(): void
    {
        // Permissions and their assignments may be in use; preserve them on rollback.
    }
};
