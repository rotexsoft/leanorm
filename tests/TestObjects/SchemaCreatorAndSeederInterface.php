<?php
/**
 * @author rotimi
 */
interface SchemaCreatorAndSeederInterface {

    public function createSchema(): bool;
    public function createTables(): bool;
    public function populateTables(): bool;
}
