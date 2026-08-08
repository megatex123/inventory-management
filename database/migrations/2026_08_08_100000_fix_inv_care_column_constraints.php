<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * inv_care was originally created with unit_cost as int(20) (silently
 * truncating cents) and warranty_starts/warranty_ends/manufacturer/
 * serial_label as NOT NULL, even though none of these are ever set by the
 * create/edit UI -- forcing the controller to fabricate a fake "now()"
 * warranty date on every create. Makes unit_cost a proper decimal and the
 * unused-by-the-UI fields nullable.
 *
 * Uses raw ALTER TABLE / MODIFY instead of Schema::table()->change() --
 * see docs/QuiviTech/Dev-Setup.md's "Doctrine DBAL is broken" gotcha,
 * ->change() requires doctrine/dbal which doesn't work with this
 * project's Laravel 7 + Carbon 2.73 combo.
 */
class FixInvCareColumnConstraints extends Migration
{
    public function up()
    {
        $column = DB::selectOne("SHOW COLUMNS FROM inv_care WHERE Field = 'unit_cost'");
        if ($column && stripos($column->Type, 'int') !== false) {
            DB::statement('ALTER TABLE inv_care MODIFY unit_cost DECIMAL(12,2) NOT NULL DEFAULT 0');
        }

        $this->modifyIfNotNullable('warranty_starts', 'ALTER TABLE inv_care MODIFY warranty_starts DATETIME NULL');
        $this->modifyIfNotNullable('warranty_ends', 'ALTER TABLE inv_care MODIFY warranty_ends DATETIME NULL');
        $this->modifyIfNotNullable('manufacturer', "ALTER TABLE inv_care MODIFY manufacturer VARCHAR(100) NULL");
        $this->modifyIfNotNullable('serial_label', 'ALTER TABLE inv_care MODIFY serial_label INT(20) NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE inv_care MODIFY unit_cost INT(20) NOT NULL DEFAULT 0');
        DB::statement("UPDATE inv_care SET warranty_starts = NOW() WHERE warranty_starts IS NULL");
        DB::statement("UPDATE inv_care SET warranty_ends = NOW() WHERE warranty_ends IS NULL");
        DB::statement("UPDATE inv_care SET manufacturer = '' WHERE manufacturer IS NULL");
        DB::statement("UPDATE inv_care SET serial_label = 0 WHERE serial_label IS NULL");
        DB::statement('ALTER TABLE inv_care MODIFY warranty_starts DATETIME NOT NULL');
        DB::statement('ALTER TABLE inv_care MODIFY warranty_ends DATETIME NOT NULL');
        DB::statement("ALTER TABLE inv_care MODIFY manufacturer VARCHAR(100) NOT NULL");
        DB::statement('ALTER TABLE inv_care MODIFY serial_label INT(20) NOT NULL');
    }

    private function modifyIfNotNullable($field, $sql)
    {
        $column = DB::selectOne("SHOW COLUMNS FROM inv_care WHERE Field = ?", [$field]);
        if ($column && $column->Null === 'NO') {
            DB::statement($sql);
        }
    }
}
