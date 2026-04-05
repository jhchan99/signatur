<?php

use Illuminate\Support\Facades\Schema;

it('keeps authors.open_library_id after all migrations including the ensure migration', function () {
    expect(Schema::hasColumn('authors', 'open_library_id'))->toBeTrue();
});
