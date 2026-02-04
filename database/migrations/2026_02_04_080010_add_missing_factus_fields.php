<?php

// database/migrations/2026_02_04_xxxxx_add_missing_factus_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Agregar solo las columnas que NO existen

            if (!Schema::hasColumn('ventas', 'facturada')) {
                $table->boolean('facturada')->default(false)->after('restante');
            }

            if (!Schema::hasColumn('ventas', 'factus_numero')) {
                $table->string('factus_numero')->nullable()->after('factus_id');
            }

            // Cambiar nombre de 'cufe' a 'factus_cufe' para consistencia
            if (Schema::hasColumn('ventas', 'cufe') && !Schema::hasColumn('ventas', 'factus_cufe')) {
                $table->renameColumn('cufe', 'factus_cufe');
            }

            if (!Schema::hasColumn('ventas', 'factus_qr')) {
                $table->text('factus_qr')->nullable()->after('factus_cufe');
            }

            // Cambiar nombre de 'pdf_url' a 'factus_pdf_url' para consistencia
            if (Schema::hasColumn('ventas', 'pdf_url') && !Schema::hasColumn('ventas', 'factus_pdf_url')) {
                $table->renameColumn('pdf_url', 'factus_pdf_url');
            }

            if (!Schema::hasColumn('ventas', 'factus_public_url')) {
                $table->text('factus_public_url')->nullable()->after('factus_pdf_url');
            }

            if (!Schema::hasColumn('ventas', 'factus_respuesta')) {
                $table->json('factus_respuesta')->nullable()->after('factus_public_url');
            }

            if (!Schema::hasColumn('ventas', 'factus_error')) {
                $table->text('factus_error')->nullable()->after('factus_respuesta');
            }

            if (!Schema::hasColumn('ventas', 'factus_fecha_generacion')) {
                $table->timestamp('factus_fecha_generacion')->nullable()->after('factus_error');
            }

            // Índices
            if (!Schema::hasIndex('ventas', 'ventas_facturada_index')) {
                $table->index('facturada');
            }

            if (!Schema::hasIndex('ventas', 'ventas_factus_numero_index')) {
                $table->index('factus_numero');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Revertir renombres si es necesario
            if (Schema::hasColumn('ventas', 'factus_cufe') && !Schema::hasColumn('ventas', 'cufe')) {
                $table->renameColumn('factus_cufe', 'cufe');
            }

            if (Schema::hasColumn('ventas', 'factus_pdf_url') && !Schema::hasColumn('ventas', 'pdf_url')) {
                $table->renameColumn('factus_pdf_url', 'pdf_url');
            }

            // Eliminar columnas agregadas (opcional)
            $columnsToDrop = [
                'facturada',
                'factus_numero',
                'factus_qr',
                'factus_public_url',
                'factus_respuesta',
                'factus_error',
                'factus_fecha_generacion'
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('ventas', $column)) {
                    $table->dropColumn($column);
                }
            }

            // Eliminar índices
            $table->dropIndexIfExists('ventas_facturada_index');
            $table->dropIndexIfExists('ventas_factus_numero_index');
        });
    }
};
