<?php
/**
 *
 * @author rotimi
 */
interface SchemaCreatorAndSeederInterface {

    public function createTables(): bool;
    public function populateTables(): bool;
}
