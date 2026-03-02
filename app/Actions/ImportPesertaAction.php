<?php

namespace App\Actions;

use App\Enums\AccessStatus;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Invitation;
use App\Models\Participant;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportPesertaAction
{
    /**
     * Import participants from an Excel/CSV file into an event.
     *
     * @return array{imported: int, skipped: int, errors: int, error_rows: list<array<string, mixed>>, skipped_rows: list<array<string, mixed>>}
     */
    public function execute(Event $event, string $filePath): array
    {
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        $errorRows = [];
        $skippedRows = [];

        $rows = (new FastExcel)->import($filePath);

        foreach ($rows as $index => $row) {
            $rowNum = (int) $index + 2;

            // Normalize column keys to lowercase.
            $row = array_change_key_case($row, CASE_LOWER);

            $nama = trim($row['nama'] ?? '');
            $noHp = trim((string) ($row['no_hp'] ?? ''));

            if ($nama === '') {
                $errors++;
                $errorRows[] = ['baris' => $rowNum, 'no_hp' => $noHp, 'alasan' => 'Nama kosong'];

                continue;
            }

            $phoneE164 = $this->normalizePhone($noHp);

            if ($phoneE164 === null) {
                $errors++;
                $errorRows[] = ['baris' => $rowNum, 'nama' => $nama, 'no_hp' => $noHp, 'alasan' => 'Nomor HP tidak valid'];

                continue;
            }

            // Check if this phone is already enrolled in the event.
            $existingParticipant = Participant::where('phone_e164', $phoneE164)->first();

            if ($existingParticipant) {
                $alreadyEnrolled = EventParticipant::where('event_id', $event->id)
                    ->where('participant_id', $existingParticipant->id)
                    ->exists();

                if ($alreadyEnrolled) {
                    $skipped++;
                    $skippedRows[] = ['baris' => $rowNum, 'nama' => $nama, 'no_hp' => $phoneE164];

                    continue;
                }
            }

            // Collect extra columns as meta.
            $reservedKeys = ['nama', 'no_hp'];
            $meta = [];
            foreach ($row as $key => $value) {
                if (! in_array(strtolower($key), $reservedKeys)) {
                    $value = trim((string) $value);
                    if ($value !== '') {
                        $meta[$key] = $value;
                    }
                }
            }

            $participant = Participant::firstOrCreate(
                ['phone_e164' => $phoneE164],
                ['name' => $nama, 'meta' => empty($meta) ? null : $meta],
            );

            $enrollment = EventParticipant::create([
                'event_id' => $event->id,
                'participant_id' => $participant->id,
                'access_status' => AccessStatus::Allowed->value,
            ]);

            $rawToken = bin2hex(random_bytes(32));

            Invitation::create([
                'event_participant_id' => $enrollment->id,
                'token_hash' => hash('sha256', $rawToken),
                'token' => $rawToken,
                'invitation_code' => Invitation::nextCodeForEvent($event),
                'issued_at' => now(),
                'expires_at' => $event->end_at,
            ]);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
            'error_rows' => $errorRows,
            'skipped_rows' => $skippedRows,
        ];
    }

    private function normalizePhone(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        try {
            $util = PhoneNumberUtil::getInstance();
            $number = $util->parse($raw, 'ID');

            if (! $util->isValidNumber($number)) {
                return null;
            }

            return $util->format($number, PhoneNumberFormat::E164);
        } catch (NumberParseException) {
            return null;
        }
    }
}
