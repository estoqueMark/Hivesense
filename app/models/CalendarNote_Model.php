<?php
require_once __DIR__ . "/Base_Model.php";

class CalendarNote_Model extends Base_Model {

    public function __construct(){
        parent::__construct();
    }

    public function getDatesWithNotes(string $yearMonth, ?int $sensorId = null): array {
        if ($sensorId) {
            $stmt = $this->connection->prepare(
                'SELECT DISTINCT DATE_FORMAT(note_date, "%Y-%m-%d") AS note_date
                 FROM hs_calendar_notes
                 WHERE DATE_FORMAT(note_date, "%Y-%m") = ?
                   AND sensor_id = ?'
            );
            $stmt->bind_param("si", $yearMonth, $sensorId);
        } else {
            $stmt = $this->connection->prepare(
                'SELECT DISTINCT DATE_FORMAT(note_date, "%Y-%m-%d") AS note_date
                 FROM hs_calendar_notes
                 WHERE DATE_FORMAT(note_date, "%Y-%m") = ?'
            );
            $stmt->bind_param("s", $yearMonth);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        return array_column($rows, 'note_date');
    }

    public function getNoteDatesForHive(int $sensorId): array {
        $stmt = $this->connection->prepare(
            'SELECT note_id, DATE_FORMAT(note_date, "%Y-%m-%d") AS note_date
             FROM hs_calendar_notes
             WHERE sensor_id = ?
             ORDER BY note_date DESC'
        );
        $stmt->bind_param("i", $sensorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getNotesForExport(int $sensorId): array {
        $stmt = $this->connection->prepare(
            'SELECT n.*, s.hive_name
             FROM hs_calendar_notes n
             LEFT JOIN hs_sensors s ON s.sensor_id = n.sensor_id
             WHERE n.sensor_id = ?
             ORDER BY n.note_date ASC'
        );
        $stmt->bind_param("i", $sensorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getNoteById(int $noteId): ?array {
        $stmt = $this->connection->prepare(
            'SELECT n.*, s.hive_name
             FROM hs_calendar_notes n
             LEFT JOIN hs_sensors s ON s.sensor_id = n.sensor_id
             WHERE n.note_id = ?
             LIMIT 1'
        );
        $stmt->bind_param("i", $noteId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function createNote(array $data): int {
        $note_date              = $data['note_date']              ?? null;
        $sensor_id               = ($data['sensor_id']               !== null && $data['sensor_id']               !== '') ? (string)(int)$data['sensor_id']               : null;
        $num_colonies            = ($data['num_colonies']            !== null && $data['num_colonies']            !== '') ? $data['num_colonies']            : null;
        $queen_species           = ($data['queen_species']           !== null && $data['queen_species']           !== '') ? $data['queen_species']           : null;
        $queen_age               = ($data['queen_age']               !== null && $data['queen_age']               !== '') ? $data['queen_age']               : null;
        $comb_frames_change      = ($data['comb_frames_change']      !== null && $data['comb_frames_change']      !== '') ? (string)(int)$data['comb_frames_change']      : null;
        $wax_foundation_change   = ($data['wax_foundation_change']   !== null && $data['wax_foundation_change']   !== '') ? (string)(int)$data['wax_foundation_change']   : null;
        $feeding                 = ($data['feeding']                 !== null && $data['feeding']                 !== '') ? (string)(int)$data['feeding']                 : null;
        $colony_strength         = ($data['colony_strength']         !== null && $data['colony_strength']         !== '') ? (string)(int)$data['colony_strength']         : null;
        $brood_pattern_close     = ($data['brood_pattern_close']     !== null && $data['brood_pattern_close']     !== '') ? (string)(int)$data['brood_pattern_close']     : null;
        $brood_pattern_open      = ($data['brood_pattern_open']      !== null && $data['brood_pattern_open']      !== '') ? (string)(int)$data['brood_pattern_open']      : null;
        $brood_pattern_type      = ($data['brood_pattern_type']      !== null && $data['brood_pattern_type']      !== '') ? $data['brood_pattern_type']      : null;
        $honey_store             = ($data['honey_store']             !== null && $data['honey_store']             !== '') ? (string)(int)$data['honey_store']             : null;
        $pollen_store            = ($data['pollen_store']            !== null && $data['pollen_store']            !== '') ? (string)(int)$data['pollen_store']            : null;
        $temperament             = ($data['temperament']             !== null && $data['temperament']             !== '') ? $data['temperament']             : null;
        $treatment_date          = ($data['treatment_date']          !== null && $data['treatment_date']          !== '') ? $data['treatment_date']          : null;
        $chemical_brand          = ($data['chemical_brand']          !== null && $data['chemical_brand']          !== '') ? $data['chemical_brand']          : null;
        $remarks                 = ($data['remarks']                 !== null && $data['remarks']                 !== '') ? $data['remarks']                 : null;

        $stmt = $this->connection->prepare(
            'INSERT INTO hs_calendar_notes
               (note_date, sensor_id, num_colonies, queen_species, queen_age,
                comb_frames_change, wax_foundation_change, feeding,
                colony_strength, brood_pattern_close, brood_pattern_open, brood_pattern_type,
                honey_store, pollen_store, temperament,
                treatment_date, chemical_brand, remarks,
                created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );

        $stmt->bind_param(
            str_repeat('s', 18),
            $note_date, $sensor_id, $num_colonies, $queen_species, $queen_age,
            $comb_frames_change, $wax_foundation_change, $feeding,
            $colony_strength, $brood_pattern_close, $brood_pattern_open, $brood_pattern_type,
            $honey_store, $pollen_store, $temperament,
            $treatment_date, $chemical_brand, $remarks
        );

        $stmt->execute();
        return (int)$this->connection->insert_id;
    }

    public function updateNote(int $id, array $data): bool {
        $sensor_id               = ($data['sensor_id']               !== null && $data['sensor_id']               !== '') ? (string)(int)$data['sensor_id']               : null;
        $num_colonies            = ($data['num_colonies']            !== null && $data['num_colonies']            !== '') ? $data['num_colonies']            : null;
        $queen_species           = ($data['queen_species']           !== null && $data['queen_species']           !== '') ? $data['queen_species']           : null;
        $queen_age               = ($data['queen_age']               !== null && $data['queen_age']               !== '') ? $data['queen_age']               : null;
        $comb_frames_change      = ($data['comb_frames_change']      !== null && $data['comb_frames_change']      !== '') ? (string)(int)$data['comb_frames_change']      : null;
        $wax_foundation_change   = ($data['wax_foundation_change']   !== null && $data['wax_foundation_change']   !== '') ? (string)(int)$data['wax_foundation_change']   : null;
        $feeding                 = ($data['feeding']                 !== null && $data['feeding']                 !== '') ? (string)(int)$data['feeding']                 : null;
        $colony_strength         = ($data['colony_strength']         !== null && $data['colony_strength']         !== '') ? (string)(int)$data['colony_strength']         : null;
        $brood_pattern_close     = ($data['brood_pattern_close']     !== null && $data['brood_pattern_close']     !== '') ? (string)(int)$data['brood_pattern_close']     : null;
        $brood_pattern_open      = ($data['brood_pattern_open']      !== null && $data['brood_pattern_open']      !== '') ? (string)(int)$data['brood_pattern_open']      : null;
        $brood_pattern_type      = ($data['brood_pattern_type']      !== null && $data['brood_pattern_type']      !== '') ? $data['brood_pattern_type']      : null;
        $honey_store             = ($data['honey_store']             !== null && $data['honey_store']             !== '') ? (string)(int)$data['honey_store']             : null;
        $pollen_store            = ($data['pollen_store']            !== null && $data['pollen_store']            !== '') ? (string)(int)$data['pollen_store']            : null;
        $temperament             = ($data['temperament']             !== null && $data['temperament']             !== '') ? $data['temperament']             : null;
        $treatment_date          = ($data['treatment_date']          !== null && $data['treatment_date']          !== '') ? $data['treatment_date']          : null;
        $chemical_brand          = ($data['chemical_brand']          !== null && $data['chemical_brand']          !== '') ? $data['chemical_brand']          : null;
        $remarks                 = ($data['remarks']                 !== null && $data['remarks']                 !== '') ? $data['remarks']                 : null;

        $stmt = $this->connection->prepare(
            'UPDATE hs_calendar_notes SET
               sensor_id = ?, num_colonies = ?, queen_species = ?, queen_age = ?,
               comb_frames_change = ?, wax_foundation_change = ?, feeding = ?,
               colony_strength = ?, brood_pattern_close = ?, brood_pattern_open = ?, brood_pattern_type = ?,
               honey_store = ?, pollen_store = ?, temperament = ?,
               treatment_date = ?, chemical_brand = ?, remarks = ?,
               updated_at = NOW()
             WHERE note_id = ?'
        );

        $stmt->bind_param(
            str_repeat('s', 17) . 'i',
            $sensor_id, $num_colonies, $queen_species, $queen_age,
            $comb_frames_change, $wax_foundation_change, $feeding,
            $colony_strength, $brood_pattern_close, $brood_pattern_open, $brood_pattern_type,
            $honey_store, $pollen_store, $temperament,
            $treatment_date, $chemical_brand, $remarks,
            $id
        );

        return $stmt->execute();
    }

    public function deleteNote(int $id): bool {
        $stmt = $this->connection->prepare('DELETE FROM hs_calendar_notes WHERE note_id = ?');
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
?>