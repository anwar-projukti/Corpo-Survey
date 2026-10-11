<?php

namespace App\Support;

use Maatwebsite\Excel\Concerns\Import;

/**
 * A no-op marker used with Excel::toCollection()/toArray() (spec §10.4's
 * import upload/preview steps) — those methods need a Maatwebsite Import
 * instance to dispatch to, but reading a plain array of rows needs no
 * per-row behavior of its own.
 */
class RawSpreadsheetReader implements Import {}
