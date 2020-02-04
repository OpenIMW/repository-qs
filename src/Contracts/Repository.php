<?php

namespace IMW\RepositoryQS\Contracts;

interface Repository
{
    /**
     * List records.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function list();

    /**
     * Add new record.
     *
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function put(array $data);

    /**
     * Show single record.
     *
     * @param mixed $record
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function show($record);

    /**
     * Update the given record.
     *
     * @param mixed $record
     * @param array $data
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function update($record, array $data);

    /**
     * Destroy the given record(s).
     *
     * @param mixed $record
     *
     * @return void
     */
    public function destroy($record);

    /**
     * Force delete the given record(s).
     *
     * @param mixed $record
     *
     * @return void
     */
    public function forceDestroy($record);
}
