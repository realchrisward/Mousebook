<?php
/**
 * includes/write.php — mb_write(): the one place a tracked record changes.
 *
 * WHY A CHOKEPOINT
 * ----------------
 * Four items on the board need the same thing, and each of them is unbuildable
 * without it:
 *
 *   #27a  optimistic locking  — needs ONE place to check a version token
 *   #46   audit trail         — needs ONE place to log a change
 *   E-1   CSRF                — needs ONE place to verify a token
 *   C-2   atomic idno claim   — needs a real transaction around read-then-write
 *
 * Built separately, each of those becomes ~20 edits that must all be right and
 * must all STAY right. Built here, each is one edit in one function.
 *
 * WHAT IT GUARANTEES
 * ------------------
 *   1. The before-image is read with FOR UPDATE, so the row is locked for the
 *      life of the transaction. Read-then-write stops being a race.
 *   2. Only columns that actually CHANGED are written -- and therefore only
 *      changed columns are logged. An audit trail that records "user saved the
 *      form" for every field is noise; one that records "dod: null -> 2026-07-01"
 *      is evidence.
 *   3. The record change and its audit rows commit or roll back TOGETHER. A
 *      colony whose data says one thing and whose audit log says another is
 *      worse than no audit log, because it is trusted.
 *
 * This file requires a transactional engine. That is the whole reason Track B-1
 * converted the schema to InnoDB before this was written: on MyISAM, points 1
 * and 3 above are not merely unimplemented, they are impossible.
 */

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------------
// Nestable transactions.
//
// mb_write() opens a transaction. A page that updates six animals in one submit
// wants ONE transaction around all six, not six. So the helpers count depth: the
// outermost begin starts the transaction, the outermost commit ends it, and the
// inner ones are bookkeeping. Any rollback, at any depth, poisons the whole
// transaction -- a partial commit of a failed multi-record save is exactly the
// silent corruption this file exists to prevent.
// ---------------------------------------------------------------------------
function &mb_tx_state(mysqli $conn): array
{
    static $state = [];
    $key = spl_object_id($conn);
    if (!isset($state[$key])) {
        $state[$key] = ['depth' => 0, 'poisoned' => false];
    }
    return $state[$key];
}

function mb_tx_begin(mysqli $conn): bool
{
    $st =& mb_tx_state($conn);
    if ($st['depth'] === 0) {
        if (!$conn->begin_transaction()) {
            return false;
        }
        $st['poisoned'] = false;
    }
    $st['depth']++;
    return true;
}

function mb_tx_commit(mysqli $conn): bool
{
    $st =& mb_tx_state($conn);
    if ($st['depth'] === 0) {
        return false;                       // commit without begin: a bug, not a no-op
    }
    $st['depth']--;
    if ($st['depth'] > 0) {
        return true;                        // inner commit: the outermost one decides
    }
    if ($st['poisoned']) {
        $conn->rollback();                  // something inside failed; never commit part of it
        $st['poisoned'] = false;
        return false;
    }
    return $conn->commit();
}

function mb_tx_rollback(mysqli $conn): bool
{
    $st =& mb_tx_state($conn);
    if ($st['depth'] === 0) {
        return false;
    }
    $st['poisoned'] = true;                 // outermost commit will now roll back instead
    $st['depth']--;
    if ($st['depth'] === 0) {
        $st['poisoned'] = false;
        return $conn->rollback();
    }
    return true;
}

// ---------------------------------------------------------------------------
// Identifier whitelisting.
//
// Table and column names CANNOT be bound as parameters -- only values can. So
// every identifier that reaches SQL from here is checked against a strict
// pattern first. This is the rule E-2 generalises: values bound, identifiers
// whitelisted, nothing interpolated.
// ---------------------------------------------------------------------------
function mb_ident_ok($name): bool
{
    return is_string($name) && $name !== '' && preg_match('/^[A-Za-z0-9_]{1,64}$/', $name) === 1;
}

// ---------------------------------------------------------------------------
// Value comparison for the diff. THE DIFF IS ONLY AS GOOD AS THIS FUNCTION.
//
// Naive string comparison is wrong here, and wrong in a way that hides:
//
//     `dob` is DATETIME. The form posts "2026-01-01". The column holds
//     "2026-01-01 00:00:00". As strings those differ -- so a naive diff calls
//     dob CHANGED on every single save, rewrites a column nobody touched, and
//     (once Track D lands) writes an audit row saying
//         dob: 2026-01-01 00:00:00 -> 2026-01-01
//     every time anyone opens and saves the form. Forever.
//
// An audit trail that cries wolf on every save is worse than none: people stop
// reading it, and the one real change is buried in a thousand fake ones. So the
// comparison is TYPE-AWARE -- values are compared as the COLUMN defines them,
// not as PHP happens to have them.
//
// NULL and '' stay distinct: dob NULL means "unknown", which is not the same
// claim as an empty string, and conflating them would quietly invent data.
// ---------------------------------------------------------------------------

// Column types for a table, read once from information_schema and cached for the
// request. One extra query per table per request, in exchange for a diff that is
// actually correct.
function mb_column_types(mysqli $conn, string $table): array
{
    static $cache = [];
    $key = $conn->thread_id . '|' . $table;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $types = [];
    $st = $conn->prepare(
        'SELECT column_name, data_type FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = ?'
    );
    if ($st) {
        $st->bind_param('s', $table);
        if ($st->execute()) {
            $res = $st->get_result();
            while ($row = $res->fetch_assoc()) {
                // MySQL 8 returns these keys uppercase, MariaDB lowercase.
                $vals = array_change_key_case($row, CASE_LOWER);
                $types[$vals['column_name']] = strtolower((string)$vals['data_type']);
            }
        }
        $st->close();
    }
    $cache[$key] = $types;
    return $types;
}

function mb_values_differ($old, $new, string $type = ''): bool
{
    if ($old === null || $new === null) {
        return $old !== $new;
    }

    switch ($type) {
        case 'date':
        case 'datetime':
        case 'timestamp':
            // Compare as instants, not as text. "2026-01-01" and
            // "2026-01-01 00:00:00" are the same moment and must not be a diff.
            $a = strtotime((string)$old);
            $b = strtotime((string)$new);
            if ($a === false || $b === false) {
                break;                       // unparseable: fall through to string compare
            }
            if ($type === 'date') {
                return date('Y-m-d', $a) !== date('Y-m-d', $b);
            }
            return $a !== $b;

        case 'tinyint':  case 'smallint': case 'mediumint':
        case 'int':      case 'integer':  case 'bigint':
            // "5" and 5 are the same value. Only compare numerically when both
            // sides really are numeric -- otherwise this would silently equate
            // "abc" and "def" (both == 0 in a loose compare).
            if (is_numeric($old) && is_numeric($new)) {
                return (int)$old !== (int)$new;
            }
            break;

        case 'decimal': case 'float': case 'double':
            if (is_numeric($old) && is_numeric($new)) {
                return (float)$old !== (float)$new;
            }
            break;
    }

    return (string)$old !== (string)$new;
}

/**
 * mb_write() — update one record, atomically, with an audit trail.
 *
 * @param mysqli $conn
 * @param string $table       whitelisted identifier
 * @param string $pk_col      whitelisted identifier
 * @param mixed  $pk_val      bound
 * @param array  $new_values  column => value. Columns whitelisted, values bound.
 *                            A value of null writes SQL NULL.
 * @param array  $opts        'actor'      => string   (defaults to mb_actor())
 *                            'request_id' => string   (defaults to mb_request_id(true))
 *                            'expect'     => array    column => expected before-value.
 *                                            The optimistic-locking hook (#27a / C-1):
 *                                            if the row no longer matches, the write is
 *                                            refused rather than silently overwriting a
 *                                            change made by somebody else.
 *
 * @return array status: 'updated' | 'unchanged' | 'notfound' | 'conflict' | 'error'
 *               changed: [col => ['old' => ..., 'new' => ...]]
 *               error:   string, when status is 'error'
 */
function mb_write(mysqli $conn, string $table, string $pk_col, $pk_val, array $new_values, array $opts = []): array
{
    $out = ['status' => 'error', 'changed' => [], 'error' => ''];

    if (!mb_ident_ok($table) || !mb_ident_ok($pk_col)) {
        $out['error'] = 'invalid table or primary-key identifier';
        return $out;
    }
    $cols = array_keys($new_values);
    foreach ($cols as $c) {
        if (!mb_ident_ok($c)) {
            $out['error'] = 'invalid column identifier: ' . (string)$c;
            return $out;
        }
    }
    if (!$cols) {
        $out['status'] = 'unchanged';
        return $out;
    }

    $actor   = (string)($opts['actor'] ?? mb_actor());
    $request = (string)($opts['request_id'] ?? mb_request_id(true));
    $expect  = (array)($opts['expect'] ?? []);

    if (!mb_tx_begin($conn)) {
        $out['error'] = 'could not begin transaction: ' . $conn->error;
        return $out;
    }

    try {
        // ---- before-image, row locked for the life of the transaction --------
        $read_cols = array_unique(array_merge($cols, array_keys($expect)));
        $select = 'SELECT `' . implode('`,`', $read_cols) . '` FROM `' . $table . '`'
                . ' WHERE `' . $pk_col . '` = ? FOR UPDATE';
        $st = $conn->prepare($select);
        if (!$st) {
            throw new RuntimeException('prepare(before-image) failed: ' . $conn->error);
        }
        $st->bind_param('s', $pk_val);
        if (!$st->execute()) {
            throw new RuntimeException('before-image failed: ' . $st->error);
        }
        $before = $st->get_result()->fetch_assoc();
        $st->close();

        $types = mb_column_types($conn, $table);

        if ($before === null) {
            mb_tx_rollback($conn);
            $out['status'] = 'notfound';
            return $out;
        }

        // ---- optimistic lock (C-1 plugs in here, and nowhere else) -----------
        foreach ($expect as $col => $want) {
            if (mb_values_differ($before[$col], $want, $types[$col] ?? '')) {
                mb_tx_rollback($conn);
                $out['status']  = 'conflict';
                $out['changed'] = [$col => ['old' => $want, 'new' => $before[$col]]];
                return $out;
            }
        }

        // ---- diff: write only what actually changed --------------------------
        $changed = [];
        foreach ($new_values as $col => $val) {
            if (mb_values_differ($before[$col], $val, $types[$col] ?? '')) {
                $changed[$col] = ['old' => $before[$col], 'new' => $val];
            }
        }
        if (!$changed) {
            mb_tx_commit($conn);            // released the row lock; nothing written, nothing logged
            $out['status'] = 'unchanged';
            return $out;
        }

        // ---- the write -------------------------------------------------------
        $sets = [];
        $bind = [];
        foreach ($changed as $col => $d) {
            $sets[] = '`' . $col . '` = ?';
            $bind[] = $d['new'];
        }
        $bind[] = $pk_val;
        $sql = 'UPDATE `' . $table . '` SET ' . implode(', ', $sets)
             . ' WHERE `' . $pk_col . '` = ?';
        $st = $conn->prepare($sql);
        if (!$st) {
            throw new RuntimeException('prepare(update) failed: ' . $conn->error);
        }
        // Everything binds as a string: MySQL casts on the way in, and 's' keeps
        // NULLs intact (bind_param passes PHP null through as SQL NULL).
        $st->bind_param(str_repeat('s', count($bind)), ...$bind);
        if (!$st->execute()) {
            throw new RuntimeException('update failed: ' . $st->error);
        }
        $st->close();

        // ---- audit, INSIDE the same transaction ------------------------------
        // Track D supplies mb_audit_record(). Until it does, this is a no-op --
        // deliberately: shipping the chokepoint before the audit schema means the
        // write paths are already routed through it when Track D lands, so the
        // audit trail becomes ONE new function rather than another sweep of every
        // page. The call sits inside the transaction so it can never be added
        // later in a way that lets the data and the log disagree.
        if (function_exists('mb_audit_record')) {
            if (!mb_audit_record($conn, $table, $pk_col, $pk_val, $changed, $actor, $request)) {
                throw new RuntimeException('audit write failed - record change rolled back');
            }
        }

        if (!mb_tx_commit($conn)) {
            throw new RuntimeException('commit failed: ' . $conn->error);
        }

        $out['status']  = 'updated';
        $out['changed'] = $changed;
        return $out;

    } catch (\Throwable $e) {
        mb_tx_rollback($conn);
        $out['status'] = 'error';
        $out['error']  = $e->getMessage();
        return $out;
    }
}
